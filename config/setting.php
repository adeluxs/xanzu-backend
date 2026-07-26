<?php

return [
    'global' => [
        'title' => 'Global Settings',

        'elements' => [
            [
                'type' => 'file', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'site_logo', // unique name for field
                'label' => 'Site Logo', // you know what label it is
                'rules' => 'mimes:jpeg,jpg,png,svg|max:1000', // validation rule of laravel
                'value' => 'default/fav.png', // default value if you want
            ],
            [
                'type' => 'file', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'site_logo_dark', // unique name for field
                'label' => 'Site Logo (Dark)', // you know what label it is
                'rules' => 'mimes:jpeg,jpg,png,svg|max:1000', // validation rule of laravel
                'value' => 'default/fav.png', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'site_logo_height', // unique name for field
                'label' => 'Site Logo Height', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '0', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'site_logo_width', // unique name for field
                'label' => 'Site Logo Width', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '0', // default value if you want
            ],
            [
                'type' => 'file', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'site_favicon', // unique name for field
                'label' => 'Site Favicon', // you know what label it is
                'rules' => 'mimes:jpeg,jpg,png,svg|max:1000', // validation rule of laravel
                'value' => 'image/logo.png', // default value if you want
            ],
            [
                'type' => 'file', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'login_bg', // unique name for field
                'label' => 'Admin Login Cover', // you know what label it is
                'rules' => 'mimes:jpeg,jpg,png,svg|max:2000', // validation rule of laravel
                'value' => 'default/auth-bg.jpg', // default value if you want
            ],

            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'site_admin_prefix', // unique name for field
                'label' => 'Site Admin Prefix', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'admin', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'site_title', // unique name for field
                'label' => 'Site Title', // you know what label it is
                'rules' => 'required|min:2|max:50', // validation rule of laravel
                'value' => 'Xanzu', // default value if you want
            ],

            // [
            //     'type' => 'switch', // input fields type
            //     'data' => 'string', // data type, string, int, boolean
            //     'name' => 'site_currency_type', // unique name for field
            //     'label' => 'Site Currency Type', // you know what label it is
            //     'rules' => 'required', // validation rule of laravel
            //     'value' => 'fiat', // default value if you want
            // ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'site_currency', // unique name for field
                'label' => 'Site Currency', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'USD', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'currency_symbol', // unique name for field
                'label' => 'Currency Symbol', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '$', // default value if you want
            ],
            [
                'type' => 'dropdown', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'site_timezone', // unique name for field
                'label' => 'Site Timezone', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'UTC', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'integer', // data type, string, int, boolean
                'name' => 'referral_code_limit', // unique name for field
                'label' => 'Referral Code Limit', // you know what label it is
                'rules' => 'required|numeric|max:36', // validation rule of laravel
                'value' => '6', // default value if you want
            ],
            [
                'type' => 'dropdown', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'home_redirect', // unique name for field
                'label' => 'Home Redirect', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '/', // default value if you want
            ],
            [
                'type' => 'email', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'site_email', // unique name for field
                'label' => 'Site Email', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'admin@tdevs.co', // default value if you want
            ],
            [
                'type' => 'email', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'support_email', // unique name for field
                'label' => 'Support Email', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'support@tdevs.co', // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'referral_rules_visibility', // unique name for field
                'label' => 'Referral Rules Visibility', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '1', // default value if you want
            ],
            [
                'type' => 'textarea', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'address', // unique name for field
                'label' => 'Address', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'Xanzu', // default value if you want
            ],
        ],
    ],

    'system' => [
        'title' => 'System Settings',
        'elements' => [
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'session_lifetime', // unique name for field
                'label' => 'Session Lifetime', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '120', // default value if you want
            ],
            [
                'type' => 'select', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'ai_provider', // unique name for field
                'label' => 'AI Provider', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'ollama', // default value if you want
            ]
        ],
    ],
    'flash_sale' => [
        'title' => 'Flash Sale Settings',
        'elements' => [
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'flash_sale_status', // unique name for field
                'label' => 'Flash Sale Status', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '0', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'flash_sale_start_date', // unique name for field
                'label' => 'Flash Sale Start Date', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => now()->toDate()->format('Y-m-d'), // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'flash_sale_end_date', // unique name for field
                'label' => 'Flash Sale End Date', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => now()->toDate()->format('Y-m-d'), // default value if you want
            ],
        ],
    ],
    'delivery' => [
        'title' => 'Delivery Settings',
        'elements' => [
            [
                'type' => 'select', // input fields type
                'data' => 'json', // data type, string, int, boolean
                'name' => 'delivery_method', // unique name for field
                'label' => 'Delivery Method', // you know what label it is
                'rules' => 'required|array', // validation rule of laravel
                'value' => 'auto', // default value if you want
            ],

        ],
    ],
    'fee' => [
        'title' => 'Site Bonus, Fee Settings',
        'elements' => [
            [
                'type' => 'text', // input fields type
                'data' => 'double', // data type, string, int, boolean
                'name' => 'referral_bonus', // unique name for field
                'label' => 'Referral Bonus', // you know what label it is
                'rules' => 'required|regex:/^\d+(\.\d{1,2})?$/', // validation rule of laravel
                'value' => 20, // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'double', // data type, string, int, boolean
                'name' => 'signup_bonus', // unique name for field
                'label' => 'Sign Up Bonus', // you know what label it is
                'rules' => 'required|regex:/^\d+(\.\d{1,2})?$/', // validation rule of laravel
                'value' => 20, // default value if you want
            ],

            [
                'type' => 'checkbox', // input fields type
                'data' => 'double', // data type, string, int, boolean
                'name' => 'referral_level_free_user', // unique name for field
                'label' => 'Referral Level for Free User (Buyer)', // you know what label it is
                'rules' => 'required|numeric', // validation rule of laravel
                'value' => '1', // default value if you want
            ],

            [
                'type' => 'checkbox', // input fields type
                'data' => 'double', // data type, string, int, boolean
                'name' => 'shipping_charge', // unique name for field
                'label' => 'Shipping Charge', // you know what label it is
                'rules' => 'required|numeric', // validation rule of laravel
                'value' => '1', // default value if you want
            ],

            [
                'type' => 'checkbox', // input fields type
                'data' => 'double', // data type, string, int, boolean
                'name' => 'shipping_charge_type', // unique name for field
                'label' => 'Shipping Charge Type', // you know what label it is
                'rules' => 'required|in:fixed,percentage', // validation rule of laravel
                'value' => 'fixed', // default value if you want
            ],
        ],
    ],
    'permission' => [
        'title' => 'Permission Settings',
        'elements' => [
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'referral_signup_bonus', // unique name for field
                'label' => 'Signup Bonus', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '0', // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'email_verification', // unique name for field
                'label' => 'Email Verification', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'kyc_verification', // unique name for field
                'label' => 'ID Verification', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'fa_verification', // unique name for field
                'label' => '2FA Verification', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'otp_verification', // unique name for field
                'label' => 'OTP Verification', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'account_creation', // unique name for field
                'label' => 'Account Creation', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'buyer_deposit', // unique name for field
                'label' => 'Buyer Topup', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],

            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'preloader_status', // unique name for field
                'label' => 'Site Preloader', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],

            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'sign_up_referral', // unique name for field
                'label' => 'User Referral', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],

            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'back_to_top', // unique name for field
                'label' => 'Site Back to Top', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'site_animation', // unique name for field
                'label' => 'Site Animation', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'language_switcher', // unique name for field
                'label' => 'Language Switcher', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 0, // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'debug_mode', // unique name for field
                'label' => 'Development Mode', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 0, // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'order_review_approval', // unique name for field
                'label' => 'Order Review Approval', // you know what label it is
                'rules' => 'required|integer', // validation rule of laravel
                'value' => '1', // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'bnpl_take_initial_installment', // unique name for field
                'label' => 'Take BNPL Initial Installment During Purchase', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '0', // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'buyer_purchase', // unique name for field
                'label' => 'Buyer Purchase', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '1', // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'kyc_credit_limit', // unique name for field
                'label' => 'KYC Verified Buyer BNPL Credit', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '1', // default value if you want
            ],
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'bnpl_for_kyc_verified_only', // unique name for field
                'label' => 'BNPL for KYC Verified Only', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '1', // default value if you want
            ],
        ],
    ],

    'kyc' => [
        'title' => 'Feature Availability for ID Unverified Users',
        'elements' => [
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'kyc_buyer_deposit', // unique name for field
                'label' => 'Buyer Topup', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '0', // default value if you want
            ],

            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'kyc_purchase', // unique name for field
                'label' => 'Purchase', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '0', // default value if you want
            ],
        ],
    ],

    'inactive_user' => [
        'title' => 'Inactive User Setting',
        'elements' => [
            [
                'type' => 'switch',
                'data' => 'string',
                'name' => 'inactive_account_disabled',
                'label' => 'Inactive Account Disable',
                'rules' => 'required',
                'value' => '1',
            ],
            [
                'type' => 'text',
                'data' => 'string',
                'name' => 'inactive_days',
                'label' => 'Inactive Days',
                'rules' => 'required',
                'value' => '30',
            ],
            // [
            //     'type' => 'switch',
            //     'data' => 'string',
            //     'name' => 'inactive_account_fees',
            //     'label' => 'Inactive Account Fees',
            //     'rules' => 'required',
            //     'value' => '1',
            // ],
            // [
            //     'type' => 'double',
            //     'data' => 'string',
            //     'name' => 'fee_amount',
            //     'label' => 'Fee Amount',
            //     'rules' => 'required',
            //     'value' => '5',
            // ],
        ],
    ],

    'mail' => [
        'title' => 'Mail Settings',
        'elements' => [
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'email_from_name', // unique name for field
                'label' => 'Email From Name', // you know what label it is
                'rules' => 'required|min:5|max:50', // validation rule of laravel
                'value' => 'Tdevs', // default value if you want
            ],
            [
                'type' => 'email', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'email_from_address', // unique name for field
                'label' => 'Email From Address', // you know what label it is
                'rules' => 'required|min:5|max:50', // validation rule of laravel
                'value' => 'wd2rasel@gmail.com', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'mailing_driver', // unique name for field
                'label' => 'Mailing Driver', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'SMTP', // default value if you want
            ],

            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'mail_username', // unique name for field
                'label' => 'Mail Username', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '465', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'mail_password', // unique name for field
                'label' => 'Mail Password', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '0000', // default value if you want
            ],

            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'mail_host', // unique name for field
                'label' => 'Mail Host', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'mail.tdevs.co', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'integer', // data type, string, int, boolean
                'name' => 'mail_port', // unique name for field
                'label' => 'Mail Port', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => '465', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'mail_secure', // unique name for field
                'label' => 'Mail Secure', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'ssl', // default value if you want
            ],
        ],
    ],

    'site_maintenance' => [
        'title' => 'Site Maintenance',
        'elements' => [
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'maintenance_mode', // unique name for field
                'label' => 'Maintenance Mode', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'secret_key', // unique name for field
                'label' => 'Secret Key', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'secret', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'maintenance_title', // unique name for field
                'label' => 'Title', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'Site is not under maintenance', // default value if you want
            ],
            [
                'type' => 'textarea', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'maintenance_text', // unique name for field
                'label' => 'Maintenance Text', // you know what label it is
                'rules' => 'required|max:500', // validation rule of laravel
                'value' => 'Sorry for interrupt! Site will live soon.', // default value if you want
            ],
        ],
    ],

    'gdpr' => [
        'title' => 'GDPR Settings',
        'elements' => [
            [
                'type' => 'checkbox', // input fields type
                'data' => 'boolean', // data type, string, int, boolean
                'name' => 'gdpr_status', // unique name for field
                'label' => 'GDPR Status', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 1, // default value if you want
            ],
            [
                'type' => 'textarea', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'gdpr_text', // unique name for field
                'label' => 'GDPR Text', // you know what label it is
                'rules' => 'required|max:500', // validation rule of laravel
                'value' => 'Please allow us to collect data about how you use our website. We will use it to improve our website, make your browsing experience and our business decisions better. Learn more', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'gdpr_button_label', // unique name for field
                'label' => 'Button Label', // you know what label it is
                'rules' => 'required|max:500', // validation rule of laravel
                'value' => 'Learn More', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'gdpr_button_url', // unique name for field
                'label' => 'Button URL', // you know what label it is
                'rules' => 'required|max:500', // validation rule of laravel
                'value' => '/privacy-policy', // default value if you want
            ],
        ],
    ],

    'meta' => [
        'elements' => [
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'meta_title', // unique name for field
                'label' => 'Meta Title', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'Buy Now, Pay Later Made Simple.', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'meta_description', // unique name for field
                'label' => 'Meta Description', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'Xanzu is a fully online banking system.', // default value if you want
            ],
            [
                'type' => 'text', // input fields type
                'data' => 'string', // data type, string, int, boolean
                'name' => 'meta_keywords', // unique name for field
                'label' => 'Meta Keywords', // you know what label it is
                'rules' => 'required', // validation rule of laravel
                'value' => 'digibank,digital banking', // default value if you want
            ],
        ],
    ],

];
