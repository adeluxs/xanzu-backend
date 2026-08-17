# Merchant transfer enablement fix

## Problem

The merchant transfer switch only supplied the default value for new accounts.
Existing merchants could retain `users.transfer_status = 0`, so the transfer API
reported that transfers were disabled even after the administrator enabled the
merchant switch.

## Corrected behavior

- Global, role, account, and KYC switches are resolved consistently by the API.
- Saving Transfer Settings synchronizes the buyer and merchant role switches to
  existing accounts.
- The included migration performs the same synchronization during deployment.
- New mobile and admin-created merchant accounts use the merchant role setting.
- An administrator can still override one account from the user's Transfer
  Status control.
- `GET /api/merchant/transfer/config` returns `role_status`, `user_status`, and
  `disabled_reason` so clients can display the correct state.
- Logs include `TRANSFER_CONFIG_RESOLVED`, `TRANSFER_SETTINGS_UPDATED`, and
  `USER_TRANSFER_STATUS_CHANGED` without exposing credentials.

## Deploy

Run these commands from the Laravel backend directory after uploading the new
source:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Then open **Admin > Transfer Settings**, confirm Global Transfer Status and
Merchant Transfer Status are enabled, and save once. The save is safe to repeat
and synchronizes any merchant accounts created from older builds.
