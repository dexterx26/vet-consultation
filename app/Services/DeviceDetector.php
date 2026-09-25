<?php

namespace App\Services;

class DeviceDetector
{
    /**
     * Parse a user agent string into human-readable platform, browser, and icon.
     *
     * @param string|null $userAgent
     * @return array{platform: string, browser: string, icon: string, full: string}
     */
    public static function parse(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return [
                'platform' => 'Unknown Device',
                'browser' => 'Web Browser',
                'icon' => 'fa-solid fa-display',
                'full' => 'Unknown Device (Web Browser)',
            ];
        }

        // Platform detection
        $platform = 'Unknown Device';
        $icon = 'fa-solid fa-display';

        if (preg_match('/windows nt 10/i', $userAgent)) {
            $platform = 'Windows PC (10/11)';
            $icon = 'fa-solid fa-laptop';
        } elseif (preg_match('/windows nt/i', $userAgent)) {
            $platform = 'Windows PC';
            $icon = 'fa-solid fa-laptop';
        } elseif (preg_match('/iphone/i', $userAgent)) {
            $platform = 'iPhone';
            $icon = 'fa-solid fa-mobile-screen-button';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $platform = 'iPad';
            $icon = 'fa-solid fa-tablet-screen-button';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android Device';
            $icon = 'fa-solid fa-mobile-screen-button';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'Apple Mac';
            $icon = 'fa-solid fa-laptop';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux Device';
            $icon = 'fa-solid fa-desktop';
        }

        // Browser detection
        $browser = 'Web Browser';
        if (preg_match('/edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/opr\//i', $userAgent) || preg_match('/opera/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/chrome|crios/i', $userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Apple Safari';
        }

        return [
            'platform' => $platform,
            'browser' => $browser,
            'icon' => $icon,
            'full' => "{$platform} ({$browser})",
        ];
    }
}
