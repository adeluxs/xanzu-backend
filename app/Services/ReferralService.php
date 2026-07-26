<?php

namespace App\Services;

use App\Models\ReferralLink;
use App\Models\ReferralProgram;
use App\Models\User;

class ReferralService
{
    /**
     * Generate referral links for a user
     *
     * @return array Generated referral links
     */
    public function generateReferralLinks(User $user)
    {
        $programs = ReferralProgram::all();
        $generatedLinks = [];

        foreach ($programs as $program) {
            $link = $user->refferelLinks()->firstOrCreate([
                'referral_program_id' => $program->id,
            ]);

            $generatedLinks[] = $link;
        }

        return $generatedLinks;
    }

    /**
     * Get or create the first referral link for a user
     *
     * @return ReferralLink|null
     */
    public function getOrCreateFirstReferralLink(User $user)
    {
        $referral = $user->getReferrals()->first();

        // If no referral link exists, create one
        if (! $referral && $programs = ReferralProgram::first()) {
            $referral = $user->refferelLinks()->create([
                'referral_program_id' => $programs->id,
            ]);
        }

        return $referral;
    }
}
