# Xanzu Backend

Laravel 12 backend API for the Xanzu fintech and e-commerce platform.

## Features

- Digital wallet management
- Buy Now Pay Later (BNPL) financing
- Virtual card issuance
- P2P peer-to-peer transfers
- E-commerce marketplace
- Multi-gateway payment support (Stripe, PayPal, Razorpay, Paytm, Perfect Money, Paystack, Voguepay, 2Checkout, Coinremitter, Coinpayments, BTCPay Server, Coinbase, Cryptomus, Coingate, RayPlusMoney)
- KYC verification
- Referral system
- Support tickets
- WooCommerce integration

## Requirements

- PHP 8.2+
- Laravel 12
- Composer
- MySQL/PostgreSQL

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan serve
```

The application can safely complete Composer package discovery and boot
Artisan before the settings, themes, gateways, or plugins tables have been
created. Settings use configured defaults, the bundled `default` theme is used,
and optional gateway/plugin configuration is deferred until migrations finish.
Legacy add/alter migrations are also guarded against imported tables that are
created later in the migration sequence, and a data-preserving repair migration
completes the core user schema on a fresh database.
Raw foreign-key constraints from the imported schema are deferred until their
referenced permission, role, order, and related tables exist. The repair is
idempotent and supports tables left behind by an interrupted migration.

For production, expose only the bundled `public` directory. The application
stores its existing public assets in the root `assets` directory, so create the
public asset link before serving it:

```bash
ln -s ../assets public/assets
```

The CyberPanel deployment commands and MozaPay API checks are documented in
`CYBERPANEL_MOZAPAY_APP_DEPLOYMENT.md`.

## Documentation

See `payment_gateway_documentation.md` for payment gateway integration details.
