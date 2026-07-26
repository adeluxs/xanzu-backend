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
php artisan migrate
php artisan serve
```

## Documentation

See `payment_gateway_documentation.md` for payment gateway integration details.
