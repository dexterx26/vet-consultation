<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MediaCompressionService
{
    /**
     * Max dimensions for compressed images (width / height).
     */
    public const MAX_IMAGE_DIMENSION = 1920;

    /**
     * JPEG compression quality (0 - 100).
     */
    public const JPEG_QUALITY = 82;

    /**
     * WebP compression quality (0 - 100).
     */
    public const WEBP_QUALITY = 82;

    /**
     * Compress media (image or video) at the given absolute file path.
     *
     * @param string $absolutePath
     * @param string $type 'image'|'video'
     * @return array [
     *   'success' => bool,
     *   'original_size' => int,
     *   'compressed_size' => int,
     *   'path' => string, // possibly modified if converted to .mp4
     *   'reduced_percent' => float
     * ]
     */
    public static function compress(string $absolutePath, string $type): array
    {
        if (!file_exists($absolutePath)) {
            return [
                'success' => false,
                'original_size' => 0,
                'compressed_size' => 0,
                'path' => $absolutePath,
                'reduced_percent' => 0,
            ];
        }

        $origSize = filesize($absolutePath);

        try {
            if ($type === 'image') {
                $newPath = self::compressImage($absolutePath);
            } elseif ($type === 'video') {
                $newPath = self::compressVideo($absolutePath);
            } else {
                $newPath = $absolutePath;
            }

            $finalSize = file_exists($newPath) ? filesize($newPath) : $origSize;
            $reducedPercent = ($origSize > 0 && $finalSize < $origSize)
                ? round((($origSize - $finalSize) / $origSize) * 100, 1)
                : 0.0;

            return [
                'success' => true,
                'original_size' => $origSize,
                'compressed_size' => $finalSize,
                'path' => $newPath,
                'reduced_percent' => $reducedPercent,
            ];
        } catch (\Throwable $e) {
            Log::warning("Media compression failed for {$absolutePath}: " . $e->getMessage());

            return [
                'success' => false,
                'original_size' => $origSize,
                'compressed_size' => $origSize,
                'path' => $absolutePath,
                'reduced_percent' => 0.0,
            ];
        }
    }

    /**
     * Compress and downscale an image in place.
     *
     * @param string $filePath
     * @return string Final file path
     */
    public static function compressImage(string $filePath): string
    {
        if (!extension_loaded('gd')) {
            return $filePath;
        }

        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return $filePath;
        }

        [$width, $height, $imageType] = $imageInfo;

        // Skip animated GIFs or unsupported types
        if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
            return $filePath;
        }

        // Create image resource
        $source = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG => @imagecreatefrompng($filePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($filePath),
            default => null,
        };

        if (!$source) {
            return $filePath;
        }

        // Handle JPEG EXIF orientation
        if ($imageType === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($filePath);
            if (!empty($exif['Orientation'])) {
                $source = match ($exif['Orientation']) {
                    3 => imagerotate($source, 180, 0),
                    6 => imagerotate($source, -90, 0),
                    8 => imagerotate($source, 90, 0),
                    default => $source,
                };
                $width = imagesx($source);
                $height = imagesy($source);
            }
        }

        // Calculate proportional target dimensions
        $maxDim = self::MAX_IMAGE_DIMENSION;
        if ($width > $maxDim || $height > $maxDim) {
            if ($width >= $height) {
                $targetWidth = $maxDim;
                $targetHeight = (int) round(($height * $maxDim) / $width);
            } else {
                $targetHeight = $maxDim;
                $targetWidth = (int) round(($width * $maxDim) / $height);
            }
        } else {
            $targetWidth = $width;
            $targetHeight = $height;
        }

        // Create destination image canvas
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        // Preserve alpha transparency for PNG and WebP
        if (in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        // High quality bicubic resampling
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        // Write to temporary output file
        $tempPath = $filePath . '.tmp';
        $saved = match ($imageType) {
            IMAGETYPE_JPEG => @imagejpeg($target, $tempPath, self::JPEG_QUALITY),
            IMAGETYPE_PNG => @imagepng($target, $tempPath, 8),
            IMAGETYPE_WEBP => @imagewebp($target, $tempPath, self::WEBP_QUALITY),
            default => false,
        };

        imagedestroy($source);
        imagedestroy($target);

        if ($saved && file_exists($tempPath)) {
            $origSize = filesize($filePath);
            $newSize = filesize($tempPath);

            // Replace original only if compressed version is smaller or was resized
            if ($newSize < $origSize || $targetWidth !== $width || $targetHeight !== $height) {
                @rename($tempPath, $filePath);
            } else {
                @unlink($tempPath);
            }
        }

        return $filePath;
    }

    /**
     * Compress a video using FFmpeg into web-optimized H.264 MP4.
     *
     * @param string $filePath
     * @return string Final file path (may have .mp4 extension if converted)
     */
    public static function compressVideo(string $filePath): string
    {
        $ffmpegPath = self::getFfmpegPath();
        if (!$ffmpegPath) {
            Log::info("FFmpeg not found; skipping video compression for {$filePath}");
            return $filePath;
        }

        $dir = dirname($filePath);
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Target output is standard web MP4
        $outputTarget = $dir . DIRECTORY_SEPARATOR . $filename . '_cmp.mp4';

        // FFmpeg command:
        // - H.264 video with CRF 26 and preset veryfast
        // - Scale down to max 1280px wide (720p) if larger, maintaining even dimensions
        // - AAC audio 128k stereo
        // - Faststart (+movflags faststart) for progressive web playback
        $cmd = sprintf(
            '"%s" -i "%s" -c:v libx264 -crf 26 -preset veryfast -vf "scale=\'min(1280,iw)\':-2" -c:a aac -b:a 128k -movflags +faststart -y "%s" 2>&1',
            $ffmpegPath,
            $filePath,
            $outputTarget
        );

        $output = [];
        $returnCode = 1;
        @exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputTarget) && filesize($outputTarget) > 0) {
            $origSize = filesize($filePath);
            $newSize = filesize($outputTarget);

            if ($newSize < $origSize || $ext !== 'mp4') {
                // If the original wasn't mp4, we replace with mp4
                $finalDestination = $dir . DIRECTORY_SEPARATOR . $filename . '.mp4';
                if ($finalDestination !== $filePath && file_exists($filePath)) {
                    @unlink($filePath);
                }
                @rename($outputTarget, $finalDestination);
                return $finalDestination;
            } else {
                // If compressed was somehow larger, keep original
                @unlink($outputTarget);
                return $filePath;
            }
        } else {
            if (file_exists($outputTarget)) {
                @unlink($outputTarget);
            }
            Log::warning("FFmpeg video compression failed for {$filePath}: " . implode(' | ', array_slice($output, -3)));
            return $filePath;
        }
    }

    /**
     * Locate FFmpeg executable across PATH and known Windows installation locations.
     *
     * @return string|null
     */
    public static function getFfmpegPath(): ?string
    {
        // 1. Direct command test in PATH
        $ret = 1;
        @exec('ffmpeg -version 2>&1', $out, $ret);
        if ($ret === 0) {
            return 'ffmpeg';
        }

        // 2. Check WinGet links in User AppData
        $userProfile = getenv('USERPROFILE') ?: ('C:\\Users\\' . (getenv('USERNAME') ?: 'dexte'));
        $wingetLink = $userProfile . '\\AppData\\Local\\Microsoft\\WinGet\\Links\\ffmpeg.exe';
        if (file_exists($wingetLink)) {
            return $wingetLink;
        }

        // 3. Check common Windows paths
        $knownPaths = [
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\xampp\\ffmpeg\\bin\\ffmpeg.exe',
        ];

        foreach ($knownPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
