<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class LinkPublicAssetsCommand extends Command
{
    /** @var string */
    protected $signature = 'app:link-public-assets
        {--force : Replace an existing symbolic link that points elsewhere}';

    /** @var string */
    protected $description = 'Expose the legacy root assets directory through public/assets';

    public function handle(): int
    {
        $source = base_path('assets');
        $target = public_path('assets');

        if (! is_dir($source)) {
            $this->error("Asset source directory does not exist: {$source}");

            return self::FAILURE;
        }

        if (is_link($target)) {
            if (realpath($target) === realpath($source)) {
                $this->info('Public assets are already linked correctly.');

                return self::SUCCESS;
            }

            if (! $this->option('force')) {
                $this->error('public/assets links elsewhere. Re-run with --force to replace only that symbolic link.');

                return self::FAILURE;
            }

            if (! unlink($target)) {
                $this->error('Unable to remove the existing public/assets symbolic link.');

                return self::FAILURE;
            }
        } elseif (file_exists($target)) {
            $this->error('public/assets already exists and is not a symbolic link. It was not modified.');

            return self::FAILURE;
        }

        try {
            if (! symlink('../assets', $target)) {
                $this->error('Unable to create public/assets. Check filesystem permissions.');

                return self::FAILURE;
            }

            Log::info('PUBLIC_ASSET_LINK_CREATED', [
                'source' => $source,
                'target' => $target,
            ]);

            $this->info('Created public/assets -> ../assets');
            $this->line('Public asset base URL: '.rtrim((string) config('app.asset_url'), '/'));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('PUBLIC_ASSET_LINK_FAILED', [
                'source' => $source,
                'target' => $target,
                'exception_type' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            $this->error('Unable to create the public asset link: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
