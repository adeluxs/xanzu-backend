<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Complete the application user schema on fresh installations.
     *
     * The project contains a minimal Laravel users migration plus an imported
     * application schema. Existing production databases already have most of
     * these columns, so every addition is deliberately idempotent.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable();
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable();
            }
            if (! Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable();
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 40)->nullable();
            }
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable();
            }
            if (! Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 30)->nullable();
            }
            if (! Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
            if (! Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable();
            }
            if (! Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable();
            }
            if (! Schema::hasColumn('users', 'zip_code')) {
                $table->string('zip_code', 30)->nullable();
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('users', 'balance')) {
                $table->decimal('balance', 28, 8)->default(0);
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->unsignedTinyInteger('status')->default(1);
            }
            if (! Schema::hasColumn('users', 'google2fa_secret')) {
                $table->text('google2fa_secret')->nullable();
            }
            if (! Schema::hasColumn('users', 'two_fa')) {
                $table->boolean('two_fa')->default(false);
            }
            if (! Schema::hasColumn('users', 'deposit_status')) {
                $table->boolean('deposit_status')->default(true);
            }
            if (! Schema::hasColumn('users', 'withdraw_status')) {
                $table->boolean('withdraw_status')->default(true);
            }
            if (! Schema::hasColumn('users', 'transfer_status')) {
                $table->boolean('transfer_status')->default(true);
            }
            if (! Schema::hasColumn('users', 'transfer_kyc_verified')) {
                $table->boolean('transfer_kyc_verified')->default(false);
            }
            if (! Schema::hasColumn('users', 'otp_status')) {
                $table->boolean('otp_status')->default(false);
            }
            if (! Schema::hasColumn('users', 'referral_status')) {
                $table->boolean('referral_status')->default(true);
            }
            if (! Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code')->nullable();
            }
            if (! Schema::hasColumn('users', 'ref_id')) {
                $table->unsignedBigInteger('ref_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'phone_verified')) {
                $table->boolean('phone_verified')->default(false);
            }
            if (! Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'otp')) {
                $table->string('otp')->nullable();
            }
            if (! Schema::hasColumn('users', 'close_reason')) {
                $table->text('close_reason')->nullable();
            }
            if (! Schema::hasColumn('users', 'show_following_follower_list')) {
                $table->boolean('show_following_follower_list')->default(true);
            }
            if (! Schema::hasColumn('users', 'accept_profile_chat')) {
                $table->boolean('accept_profile_chat')->default(true);
            }
            if (! Schema::hasColumn('users', 'kyc')) {
                $table->unsignedTinyInteger('kyc')->default(0);
            }
            if (! Schema::hasColumn('users', 'current_plan_id')) {
                $table->unsignedBigInteger('current_plan_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable();
            }
            if (! Schema::hasColumn('users', 'about')) {
                $table->text('about')->nullable();
            }
            if (! Schema::hasColumn('users', 'is_popular')) {
                $table->boolean('is_popular')->default(false);
            }
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type', 30)->default('buyer');
            }
            if (! Schema::hasColumn('users', 'total_reviews')) {
                $table->unsignedInteger('total_reviews')->default(0);
            }
            if (! Schema::hasColumn('users', 'avg_rating')) {
                $table->decimal('avg_rating', 3, 2)->default(0);
            }
            if (! Schema::hasColumn('users', 'card_status')) {
                $table->boolean('card_status')->default(false);
            }
            if (! Schema::hasColumn('users', 'default_split')) {
                $table->unsignedBigInteger('default_split')->nullable();
            }
            if (! Schema::hasColumn('users', 'notifications_permission')) {
                $table->json('notifications_permission')->nullable();
            }
            if (! Schema::hasColumn('users', 'validity_at')) {
                $table->timestamp('validity_at')->nullable();
            }
        });

        // The minimal Laravel skeleton creates a required `name` field while
        // this application stores first_name and last_name instead.
        if (Schema::hasColumn('users', 'name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('name')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Data-preserving repair: do not remove application user columns.
    }
};
