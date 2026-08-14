# Service availability and RayPlus diagnostics

## Suspend or restore all HTTP access

Run these commands from the application directory in the server terminal or hosting control-panel terminal:

```bash
# Lock customer web, mobile API, and the administrator web panel.
php artisan service:access suspend --message="Payment has not been made. Please contact the service provider to restore access."

# Check the current state without changing it.
php artisan service:access status

# Restore customer, mobile, and administrator HTTP access.
php artisan service:access restore
```

An administrator with the existing `site-setting` permission can also activate the lock under **Settings → Site Settings → Service Availability**. Saving the enabled state immediately locks that administrator out too, so it cannot be disabled from the web panel afterward. Restoration requires the server command above.

This is an explicit, audit-logged service control, not a hidden endpoint or secret client bypass. The administrator panel has no exemption. Only `/up`, the read-only `/api/get-settings` bootstrap, gateway return/status URLs, IPN callbacks, cron, and notification tuning remain reachable. These narrow technical exceptions let Flutter discover the suspension and let pending payments reconcile; they do not provide application or administrator access.

Blocked APIs return HTTP 503:

```json
{
  "success": false,
  "status": false,
  "message": "Your configured customer message",
  "code": "SERVICE_SUSPENDED",
  "data": {
    "service_suspended": true,
    "service_suspension_message": "Your configured customer message"
  },
  "errors": null,
  "status_code": 503,
  "request_id": "..."
}
```

Changes are recorded as `SERVICE_AVAILABILITY_CHANGED`; failed console changes as `SERVICE_AVAILABILITY_CHANGE_FAILED`; blocked requests as `SERVICE_SUSPENSION_REQUEST_BLOCKED`.

## Diagnose RayPlus add-money failures

Use the mobile `X-Request-ID` to correlate these events:

- `ADD_MONEY_REQUEST`
- `RAYPLUS_PAYIN_REJECTED`
- `AUTOMATIC_DEPOSIT_GATEWAY_REJECTED`
- `ADD_MONEY_GATEWAY_ERROR`

The diagnostic context includes the merchant transaction reference, provider HTTP status, provider code and message, provider request/reference ID when returned, endpoint path, payable amount/currency, response field names, and whether the failure is retryable.

The logs deliberately omit API keys, bearer tokens, payment tokens, full request/response bodies, phone/email, custom-field values, and card details.

`Echec (Code01)` proves that RayPlus received and rejected the invoice-creation request at its application layer. The exact business definition of `Code01` is not present in the supplied source and should be checked against the RayPlus merchant documentation or support response. The new diagnostic fields show whether the rejection coincides with HTTP status, amount/currency, merchant configuration, or a provider reference.
