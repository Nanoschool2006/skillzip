# PayPal PPCP — REST API & Frontend Reference

All REST routes live under the WP REST API namespace `tva/v1`.
Base URL: `/wp-json/tva/v1/paypal/`

Product API base: configured via `TVA_PAYPAL_PRODUCT_API_URL` in `wp-config.php`
(defaults to `https://thrivethemesapi.com`; use `https://tpa.stagingthrivethemes.com` on staging).

---

## Onboarding Flow Overview

```
1. POST /connect_account
       ↓ generates CSPRNG secret, calls Product API /onboarding/start
       ↓ returns { url } — PayPal sandbox/live signup URL

2. Merchant opens url in browser
       ↓ logs into PayPal, approves partner permissions
       ↓ PayPal redirects to GET /{credentials_endpoint}

3. GET /{credentials_endpoint}   ← handle_paypal_return
       ↓ validates CSRF (merchantId == stored secret)
       ↓ calls Product API /onboarding/complete
       ↓ saves merchant_id, client_id, sdk_client_token
       ↓ calls Product API /auth/token  → caches Bearer transient
       ↓ calls Product API GET /merchant → stores payments_receivable
       ↓ wp_safe_redirect → admin PayPal settings page
```

---

## Admin JS Localize Data

Available on every Thrive Apprentice admin page.

```js
{
  live_enabled: boolean,  // true = live mode connected (merchant_id + site_secret present)
  test_enabled: boolean   // true = test/sandbox mode connected
}
```

---

## Endpoints

### POST `/connect_account`

Initiate the PayPal onboarding flow. Generates a CSPRNG site secret, stores it, then
calls the Product API `/onboarding/start` to get a PayPal signup URL.

**Auth:** `manage_options` (admin only)

**Request body**

| Field | Type   | Required | Description |
|-------|--------|----------|-------------|
| mode  | string | no       | `'live'` or `'test'` — defaults to `'live'` |

**Response — success**

```json
{
  "success": true,
  "url": "https://www.sandbox.paypal.com/bizsignup/partner/entry?referralToken=..."
}
```

Redirect the merchant to `url`. PayPal will redirect them back to
`GET /{credentials_endpoint}` once they approve.

**Response — error**

```json
{
  "success": false,
  "message": "Could not start PayPal onboarding."
}
```

HTTP `500` (network failure) or `502` (Product API returned non-200 or no `url` in response).

---

### GET `/{credentials_endpoint}` and `/{credentials_endpoint_live}`

> **Browser redirect only.** PayPal redirects the merchant here after they approve
> partner permissions. This endpoint is never called by the frontend directly.

The endpoint paths are randomised per installation (stored in `wp_options`) and are
registered dynamically at boot time.

**Auth:** Public — protected by CSRF check (`hash_equals(stored_secret, merchantId)`)

**Query parameters** (sent by PayPal in the redirect URL)

| Param               | Description |
|---------------------|-------------|
| `merchantIdInPayPal` | The merchant's own PayPal account ID — stored as `merchant_id` |
| `merchantId`        | Echo of the site secret used as CSRF token |
| `permissionsGranted` | Must be `'true'` for onboarding to proceed |
| `consentStatus`     | Must be `'true'` for onboarding to proceed |

**What this endpoint does internally:**

1. Validates CSRF: `hash_equals(stored_secret, merchantId)`
2. Calls Product API `POST /onboarding/complete` with `{secret, merchant_id, referral_token, site_url, webhooks_url}`
3. Saves `merchant_id` (`merchantIdInPayPal`), `client_id`, `sdk_client_token` from the response
4. Calls Product API `POST /auth/token` to pre-warm the Bearer token transient
5. Calls Product API `GET /merchant` to verify `payments_receivable` and store it
6. Redirects to `wp-admin/admin.php?page=thrive_apprentice#settings/payments/paypal`

On any failure (CSRF mismatch, `permissionsGranted !== 'true'`, Product API error): clears
the stored secret and redirects to the same admin page without saving credentials.

**Response:** Always a browser redirect (`wp_safe_redirect`) — no JSON body.

---

### POST `/{credentials_endpoint}` and `/{credentials_endpoint_live}`

> **Retired.** The old server-to-server service API callback is no longer active.

Returns `410 Gone` for any POST to these paths.

```json
{
  "success": false,
  "message": "This endpoint is no longer active."
}
```

---

### GET `/status`

Check whether live and/or test mode are currently connected.

A mode is considered enabled when both `merchant_id` and `site_secret` are present
(32-char hex) for that mode.

**Auth:** `manage_options` (admin only)

**Response**

```json
{
  "success": true,
  "live_enabled": false,
  "test_enabled": true
}
```

| Field        | Type    | Description |
|--------------|---------|-------------|
| success      | boolean | `true` if at least one mode is connected |
| live_enabled | boolean | Live mode has `merchant_id` + `site_secret` stored |
| test_enabled | boolean | Test/sandbox mode has `merchant_id` + `site_secret` stored |

---

### DELETE `/disconnect`

Remove all stored PayPal credentials (both live and test modes).

Also makes a best-effort `DELETE /merchant` call to the Product API for each mode
that has a cached Bearer token, to clean up server-side merchant state.

**Auth:** `manage_options` (admin only)

**Response**

```json
{
  "success": true
}
```

---

### POST `/webhook`

Receives incoming PayPal webhook event notifications.

**Auth:** Public (no authentication — PayPal calls this directly)

**Phase 0:** Logs the `event_type` field when `WP_DEBUG` is enabled. Full event
handling (`PAYMENT.CAPTURE.COMPLETED`, etc.) is deferred to Phase 3 (issue #3662).

**Response**

```json
{
  "success": true
}
```

Always returns `200 OK` so PayPal does not retry delivery.

---

## Buy Now URL Generation

Not a REST endpoint — called server-side when rendering a Buy Now button.

`TVA\Buy_Now\Paypal::get_url()` creates a PayPal order via the Product API and
returns the HATEOAS approve URL for the buyer redirect.

**Returns** a string — the PayPal checkout URL, or `''` on any failure.

**Returns `''` when:**
- `is_valid()` is false — no product ID, or PayPal not connected for the given mode
- No product rule saved for the product (`Settings::get_product_rule()` returns empty)
- Product API returns a `WP_Error`
- The PayPal order response contains no `rel=approve` link

**Product rule shape** (stored via `Settings::save_product_rule($product_id, $rule)`):

```php
[
  'amount'   => '49.99',  // string — decimal price
  'currency' => 'USD',    // string — ISO 4217 currency code
]
```

**Order payload sent to Product API:**

```json
{
  "data": {
    "intent": "CAPTURE",
    "purchase_units": [
      {
        "reference_id": "42",
        "amount": {
          "currency_code": "USD",
          "value": "49.99"
        }
      }
    ],
    "payment_source": {
      "paypal": {
        "experience_context": {
          "return_url": "https://example.com/success",
          "cancel_url": "https://example.com/cancel"
        }
      }
    }
  }
}
```

`return_url` and `cancel_url` are only included when non-empty.

---

## Product API Bearer Token — Lifecycle & Auto-Refresh

The three HTTP clients (`Onboarding_Client`, `Order_Client`, `Vault_Client`) each maintain
their own bearer token for authenticating calls to the Product API.

### How it works

```
API call (e.g. create_order)
    ↓
get_bearer_token()
    ↓
get_transient('tva_paypal_bearer_{client}_{mode}')
    ↓
    ├── HIT  → return cached token, proceed with API call
    └── MISS (expired or never set)
            ↓
        POST {product_api_url}/api/paypal/v1/auth/token
        Authorization: Basic base64(merchant_id:site_secret)
            ↓
        Response: { access_token, expires_in }
            ↓
        set_transient(key, access_token, expires_in - 60)
            ↓
        return fresh token → proceed with API call
```

`merchant_id` here is the merchant's own PayPal account ID (`merchantIdInPayPal` from
the onboarding redirect) — **not** the partner merchant ID from the `/onboarding/complete`
response (`partner_merchant_id` is Thrive's fixed partner ID and is not used for auth).

### Transient keys

| Client             | Live                                | Test                                |
|--------------------|-------------------------------------|-------------------------------------|
| Onboarding_Client  | `tva_paypal_bearer_onboarding_live` | `tva_paypal_bearer_onboarding_test` |
| Order_Client       | `tva_paypal_bearer_order_live`      | `tva_paypal_bearer_order_test`      |
| Vault_Client       | `tva_paypal_bearer_vault_live`      | `tva_paypal_bearer_vault_test`      |

### Token expiry

Default TTL is 7 days (`expires_in = 604800`). The transient is stored with
`max(1, expires_in - 60)` to avoid zero/negative TTL edge cases. The refresh is
fully lazy — no cron required.

### Clearing tokens manually

```php
TVA\PayPal\Hooks::refresh_token();
```

Deletes all 6 transients (3 clients × 2 modes).

---

## Staging / Environment Override

To route all Product API calls to the staging environment, add to `wp-config.php`:

```php
define( 'TVA_PAYPAL_PRODUCT_API_URL', 'https://tpa.stagingthrivethemes.com' );
```

The default (live) URL is `https://thrivethemesapi.com`. The live Product API does not
have PayPal endpoints deployed yet — always use the staging URL for development.
