<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('transactions', ['user_id', 'method', 'created_at'], 'txn_user_method_date_idx');
        $this->addIndex('transactions', ['user_id', 'status', 'created_at'], 'txn_user_status_date_idx');
        $this->addIndex('transactions', ['from_user_id', 'created_at'], 'txn_from_user_date_idx');

        $this->addIndex('orders', ['buyer_id', 'status', 'order_date'], 'orders_buyer_status_date_idx');
        $this->addIndex('orders', ['is_bnpl', 'created_at'], 'orders_bnpl_date_idx');
        $this->addIndex('orders', ['payment_status', 'order_date'], 'orders_payment_date_idx');

        $this->addIndex('order_items', ['order_id', 'status'], 'order_items_order_status_idx');
        $this->addIndex('order_items', ['seller_id', 'status', 'created_at'], 'order_items_seller_status_idx');

        $this->addIndex('listings', ['status', 'is_approved', 'created_at'], 'listings_active_date_idx');
        $this->addIndex('listings', ['category_id', 'status', 'is_approved', 'created_at'], 'listings_category_active_idx');
        $this->addIndex('listings', ['brand_id', 'status', 'is_approved', 'created_at'], 'listings_brand_active_idx');
        $this->addIndex('listings', ['seller_id', 'status', 'is_approved', 'created_at'], 'listings_seller_active_idx');
        $this->addIndex('listings', ['status', 'is_approved', 'sold_count'], 'listings_best_selling_idx');
        $this->addIndex('listings', ['status', 'is_approved', 'is_trending', 'created_at'], 'listings_trending_idx');
        $this->addIndex('listings', ['status', 'is_approved', 'is_flash', 'created_at'], 'listings_flash_idx');

        $this->addIndex('notifications', ['for', 'user_id', 'read', 'created_at'], 'notifications_user_read_idx');
        $this->addIndex('chats', ['receiver_id', 'seen', 'created_at'], 'chats_receiver_seen_idx');
        $this->addIndex('chats', ['sender_id', 'created_at'], 'chats_sender_date_idx');
        $this->addIndex('messages', ['ticket_id', 'id'], 'messages_ticket_order_idx');
        $this->addIndex('tickets', ['user_id', 'status', 'created_at'], 'tickets_user_status_idx');

        $this->addIndex('shipping_addresses', ['user_id', 'is_default', 'id'], 'shipping_user_default_idx');
        $this->addIndex('withdraw_accounts', ['user_id', 'withdraw_method_id'], 'withdraw_accounts_user_method_idx');
        $this->addIndex('withdraw_methods', ['status', 'currency'], 'withdraw_methods_status_currency_idx');
        $this->addIndex('deposit_methods', ['status', 'currency'], 'deposit_methods_status_currency_idx');

        $this->addIndex('user_kycs', ['user_id', 'status', 'is_valid', 'created_at'], 'user_kycs_status_idx');
        $this->addIndex('cards', ['user_id', 'status', 'created_at'], 'cards_user_status_idx');
        $this->addIndex('card_applications', ['user_id', 'status', 'created_at'], 'card_apps_user_status_idx');
        $this->addIndex('listing_reviews', ['listing_id', 'status', 'parent_id', 'created_at'], 'reviews_listing_status_idx');
        $this->addIndex('listing_analysis', ['listing_id', 'event_type', 'created_at'], 'listing_analysis_listing_event_idx');

        $this->addIndex('providers', ['status', 'created_at'], 'providers_status_date_idx');
        $this->addIndex('brands', ['status', 'is_popular'], 'brands_popular_status_idx');
        $this->addIndex('categories', ['status', 'parent_id', 'is_trending', 'order'], 'categories_nav_idx');

        $this->addIndex('users', ['user_type', 'status', 'created_at'], 'users_type_status_date_idx');
        $this->addIndex('users', ['kyc', 'status'], 'users_kyc_status_idx');
        $this->addIndex('users', ['ref_id', 'created_at'], 'users_referral_date_idx');
    }

    public function down(): void
    {
        foreach ([
            ['transactions', 'txn_user_method_date_idx'],
            ['transactions', 'txn_user_status_date_idx'],
            ['transactions', 'txn_from_user_date_idx'],
            ['orders', 'orders_buyer_status_date_idx'],
            ['orders', 'orders_bnpl_date_idx'],
            ['orders', 'orders_payment_date_idx'],
            ['order_items', 'order_items_order_status_idx'],
            ['order_items', 'order_items_seller_status_idx'],
            ['listings', 'listings_active_date_idx'],
            ['listings', 'listings_category_active_idx'],
            ['listings', 'listings_brand_active_idx'],
            ['listings', 'listings_seller_active_idx'],
            ['listings', 'listings_best_selling_idx'],
            ['listings', 'listings_trending_idx'],
            ['listings', 'listings_flash_idx'],
            ['notifications', 'notifications_user_read_idx'],
            ['chats', 'chats_receiver_seen_idx'],
            ['chats', 'chats_sender_date_idx'],
            ['messages', 'messages_ticket_order_idx'],
            ['tickets', 'tickets_user_status_idx'],
            ['shipping_addresses', 'shipping_user_default_idx'],
            ['withdraw_accounts', 'withdraw_accounts_user_method_idx'],
            ['withdraw_methods', 'withdraw_methods_status_currency_idx'],
            ['deposit_methods', 'deposit_methods_status_currency_idx'],
            ['user_kycs', 'user_kycs_status_idx'],
            ['cards', 'cards_user_status_idx'],
            ['card_applications', 'card_apps_user_status_idx'],
            ['listing_reviews', 'reviews_listing_status_idx'],
            ['listing_analysis', 'listing_analysis_listing_event_idx'],
            ['providers', 'providers_status_date_idx'],
            ['brands', 'brands_popular_status_idx'],
            ['categories', 'categories_nav_idx'],
            ['users', 'users_type_status_date_idx'],
            ['users', 'users_kyc_status_idx'],
            ['users', 'users_referral_date_idx'],
        ] as [$table, $name]) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
            }
        }
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
    }
};
