<?php

namespace App\Console\Commands;

use App\Models\ReferralProgram;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateReferralLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:generate {--user_id= : Generate for specific user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate referral links for users who don\'t have them';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->option('user_id');

        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User with ID {$userId} not found");

                return 1;
            }

            $this->generateForUser($user);
            $this->info("Referral links generated for user {$user->username} (ID: {$user->id})");

            return 0;
        }

        $users = User::where('status', 1)->get();
        $count = 0;

        $this->info("Generating referral links for {$users->count()} users...");
        $bar = $this->output->createProgressBar($users->count());

        foreach ($users as $user) {
            $generated = $this->generateForUser($user);
            if ($generated) {
                $count++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Referral links generated for {$count} users");

        return 0;
    }

    /**
     * Generate referral links for a specific user
     *
     * @return int Number of links generated
     */
    private function generateForUser(User $user)
    {
        $programs = ReferralProgram::all();
        $count = 0;

        foreach ($programs as $program) {
            $link = $user->refferelLinks()->where('referral_program_id', $program->id)->first();

            if (! $link) {
                $user->refferelLinks()->create([
                    'referral_program_id' => $program->id,
                ]);
                $count++;
            }
        }

        return $count;
    }
}
