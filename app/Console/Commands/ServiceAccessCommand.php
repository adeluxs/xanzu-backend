<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ServiceAccessCommand extends Command
{
    private const DEFAULT_MESSAGE = 'Payment has not been made. Please contact the service provider to restore access.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service:access
        {state : suspend, restore, or status}
        {--message= : Public message displayed while access is suspended}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suspend, restore, or inspect all HTTP access to the application';

    public function handle(): int
    {
        $state = strtolower(trim((string) $this->argument('state')));

        if ($state === 'status') {
            return $this->showStatus();
        }

        if (! in_array($state, ['suspend', 'restore'], true)) {
            $this->error('State must be one of: suspend, restore, status.');

            return self::INVALID;
        }

        $before = $this->currentState();
        $suspended = $state === 'suspend';
        $message = $suspended ? $this->suspensionMessage() : $before['message'];

        try {
            DB::transaction(function () use ($suspended, $message): void {
                if ($suspended) {
                    Setting::query()->updateOrCreate(
                        ['name' => 'service_suspension_message'],
                        ['val' => $message, 'type' => 'string'],
                    );
                }

                Setting::query()->updateOrCreate(
                    ['name' => 'service_suspended'],
                    ['val' => $suspended ? 1 : 0, 'type' => 'boolean'],
                );
            });

            Setting::flushCache();
            $after = $this->currentState();

            Log::notice('SERVICE_AVAILABILITY_CHANGED', [
                'actor_type' => 'server_console',
                'command' => 'service:access '.$state,
                'host' => gethostname() ?: null,
                'before' => $before,
                'after' => $after,
            ]);

            if ($suspended) {
                $this->warn('All HTTP access is suspended, including the administrator panel.');
                $this->line('Message: '.$after['message']);
                $this->line('Restore from the server terminal with: php artisan service:access restore');
            } else {
                $this->info('HTTP access has been restored for customers, mobile users, and administrators.');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('SERVICE_AVAILABILITY_CHANGE_FAILED', [
                'actor_type' => 'server_console',
                'command' => 'service:access '.$state,
                'exception_type' => $exception::class,
            ]);

            $this->error('Unable to change service access. Check the application log and database connection.');

            return self::FAILURE;
        }
    }

    private function showStatus(): int
    {
        $current = $this->currentState();

        $this->line('HTTP access: '.($current['service_suspended'] ? 'SUSPENDED' : 'ACTIVE'));
        $this->line('Message: '.$current['message']);

        return self::SUCCESS;
    }

    /**
     * @return array{service_suspended: bool, message: string}
     */
    private function currentState(): array
    {
        return [
            'service_suspended' => setting_enabled('service_suspended', 'service_availability', false),
            'message' => trim((string) setting(
                'service_suspension_message',
                'service_availability',
                self::DEFAULT_MESSAGE,
            )),
        ];
    }

    private function suspensionMessage(): string
    {
        $option = $this->option('message');
        if ($option === null || trim((string) $option) === '') {
            return self::DEFAULT_MESSAGE;
        }

        return Str::limit(Str::squish((string) $option), 500, '');
    }
}
