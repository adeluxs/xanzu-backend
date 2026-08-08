<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\User;
use App\Traits\NotifyTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBulkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NotifyTrait;

    public int $tries = 2;
    public int $timeout = 300;

    /**
     * @param array<string,string> $shortcodes
     */
    public function __construct(
        public readonly string $audience,
        public readonly string $templateCode,
        public readonly string $templateFor,
        public readonly array $shortcodes,
        public readonly ?string $userType = null,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        if ($this->audience === 'subscribers') {
            Subscription::query()
                ->select(['id', 'email'])
                ->whereNotNull('email')
                ->orderBy('id')
                ->chunkById(500, function ($subscribers): void {
                    foreach ($subscribers as $subscriber) {
                        $this->sendNotify(
                            $subscriber->email,
                            $this->templateCode,
                            $this->templateFor,
                            $this->shortcodes,
                            null,
                            null,
                        );
                    }
                });

            return;
        }

        User::query()
            ->select(['id', 'email', 'phone', 'first_name', 'last_name', 'username', 'user_type'])
            ->where('status', 1)
            ->when($this->userType && $this->userType !== 'all', fn ($query) => $query->where('user_type', $this->userType))
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(300, function ($users): void {
                foreach ($users as $user) {
                    $shortcodes = array_merge($this->shortcodes, [
                        '[[full_name]]' => $user->full_name,
                    ]);

                    $this->sendNotify(
                        $user->email,
                        $this->templateCode,
                        $this->templateFor,
                        $shortcodes,
                        $user->phone,
                        $user->id,
                    );
                }
            });
    }
}
