<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SwitchEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:switch {target? : Target environment (dev, prod, status)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch application between Development (SQLite) and Production (PostgreSQL) environments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $target = strtolower($this->argument('target') ?? '');

        if (empty($target)) {
            $choice = $this->choice(
                'Select environment to switch to:',
                [
                    'dev' => 'Development (Local Laptop / SQLite database)',
                    'prod' => 'Production (Render Cloud / PostgreSQL database)',
                    'status' => 'View Current Environment Status',
                ],
                'dev'
            );
            $target = $choice;
        }

        if (in_array($target, ['status', 'info', 'check'])) {
            $this->showStatus();
            return self::SUCCESS;
        }

        if (in_array($target, ['dev', 'development', 'local', 'sqlite'])) {
            return $this->switchToDevelopment();
        }

        if (in_array($target, ['prod', 'production', 'pgsql', 'postgres', 'render'])) {
            return $this->switchToProduction();
        }

        $this->error("Invalid environment target [{$target}]. Allowed values: dev, prod, status.");
        return self::FAILURE;
    }

    /**
     * Switch to Development (SQLite)
     */
    protected function switchToDevelopment(): int
    {
        $this->info("Switching to DEVELOPMENT environment (SQLite)...");

        $devEnvPath = base_path('.env.development');
        $envPath = base_path('.env');

        if (!File::exists($devEnvPath)) {
            $this->error(".env.development template not found at: {$devEnvPath}");
            return self::FAILURE;
        }

        // Copy template to .env
        File::copy($devEnvPath, $envPath);

        // Ensure database/database.sqlite exists
        $sqlitePath = database_path('database.sqlite');
        if (!File::exists($sqlitePath)) {
            File::ensureDirectoryExists(database_path());
            File::put($sqlitePath, '');
            $this->line("  Created SQLite file at: <comment>{$sqlitePath}</comment>");
        }

        // Clean stale Vite hot file if present
        $hotPath = public_path('hot');
        if (File::exists($hotPath)) {
            File::delete($hotPath);
        }

        $this->clearLaravelCaches();

        $this->newLine();
        $this->info("Successfully switched to DEVELOPMENT mode!");
        $this->table(
            ['Setting', 'Active Value'],
            [
                ['Environment (APP_ENV)', 'local'],
                ['Debug Mode (APP_DEBUG)', 'true (Detailed Errors)'],
                ['Database Connection', 'sqlite'],
                ['Database File', 'database/database.sqlite'],
                ['Application URL', 'http://localhost:8000'],
                ['Reverb WebSockets', 'ws://localhost:8001 (HTTP)'],
                ['Session / Cache', 'database (stored in SQLite)'],
            ]
        );

        $this->comment("You can now run your local dev services using:");
        $this->line("   <info>start-dev.bat</info>  or  <info>php artisan serve</info>");

        return self::SUCCESS;
    }

    /**
     * Switch to Production (PostgreSQL)
     */
    protected function switchToProduction(): int
    {
        $this->info("Switching to PRODUCTION environment (PostgreSQL / Render)...");

        $prodEnvPath = base_path('.env.production');
        $envPath = base_path('.env');

        if (!File::exists($prodEnvPath)) {
            $this->error(".env.production template not found at: {$prodEnvPath}");
            return self::FAILURE;
        }

        // Copy template to .env
        File::copy($prodEnvPath, $envPath);

        // Clean stale Vite hot file
        $hotPath = public_path('hot');
        if (File::exists($hotPath)) {
            File::delete($hotPath);
        }

        $this->clearLaravelCaches();

        $this->newLine();
        $this->warn("Successfully switched to PRODUCTION mode!");
        $this->table(
            ['Setting', 'Active Value'],
            [
                ['Environment (APP_ENV)', 'production'],
                ['Debug Mode (APP_DEBUG)', 'false (Secure)'],
                ['Database Connection', 'pgsql (PostgreSQL)'],
                ['Database Name', 'vet_consultation (or Render DATABASE_URL)'],
                ['Application URL', 'https://vet-consultation-app.onrender.com'],
                ['Reverb WebSockets', 'wss://...:443 (HTTPS/WSS)'],
                ['Logging Channel', 'stderr (Docker / Render Cloud Logs)'],
            ]
        );

        $this->comment("Production configuration activated.");
        $this->line("   Before deploying, ensure Render environment variables or DATABASE_URL are set.");

        return self::SUCCESS;
    }

    /**
     * Show current environment status
     */
    protected function showStatus(): void
    {
        $env = env('APP_ENV', 'unknown');
        $db = env('DB_CONNECTION', 'unknown');
        $url = env('APP_URL', 'unknown');
        $debug = env('APP_DEBUG') ? 'true' : 'false';
        $reverbPort = env('REVERB_PORT', 'unknown');
        $reverbScheme = env('REVERB_SCHEME', 'unknown');

        $this->newLine();
        $this->info("Current Application Environment Status:");
        $this->table(
            ['Configuration', 'Value'],
            [
                ['Active Environment', strtoupper($env)],
                ['Debug Mode', $debug],
                ['Database Driver', $db],
                ['Database Target', $db === 'sqlite' ? database_path('database.sqlite') : (env('DATABASE_URL') ? 'Render DATABASE_URL' : env('DB_DATABASE', 'vet_consultation'))],
                ['App URL', $url],
                ['Reverb Connection', "{$reverbScheme}://...:{$reverbPort}"],
            ]
        );

        if ($db === 'sqlite') {
            $sqliteExists = File::exists(database_path('database.sqlite'));
            $this->line("SQLite File Status: " . ($sqliteExists ? '<info>Found & Ready</info>' : '<error>Missing</error>'));
        }
    }

    /**
     * Clear all Laravel caches
     */
    protected function clearLaravelCaches(): void
    {
        $this->line("  Clearing configuration and application caches...");

        // Remove compiled bootstrap cache files directly from disk
        $cachedFiles = [
            base_path('bootstrap/cache/config.php'),
            base_path('bootstrap/cache/routes-v7.php'),
            base_path('bootstrap/cache/events.php'),
            base_path('bootstrap/cache/services.php'),
            base_path('bootstrap/cache/packages.php'),
        ];

        foreach ($cachedFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Try clearing internal caches safely
        try {
            $this->callSilently('config:clear');
            $this->callSilently('route:clear');
            $this->callSilently('view:clear');
        } catch (\Throwable $e) {
            // Ignore if in-memory state conflicts
        }

        try {
            $this->callSilently('cache:clear');
        } catch (\Throwable $e) {
            // Ignore if database cache store is inaccessible during switch
        }
    }
}
