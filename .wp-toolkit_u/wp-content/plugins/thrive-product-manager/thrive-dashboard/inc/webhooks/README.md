# Thrive Dashboard – Outbound Webhooks (TD Webhooks)

## Overview

Outbound-only webhooks for sending form/user data to external endpoints when Thrive forms are submitted. Lives under Thrive Dashboard and is independent of Automator.

Quickstart (UI)
- Thrive Dashboard → Webhooks → Add New
- Fill Name, URL, Method, Request Format
- Add Body Mapping (Key/Value), Headers if needed
- Choose Trigger When (On Submit/On Success), Targeting, Consent
- Save and submit a test form; view Logs

## Storage

- Definitions: CPT `td_webhook`
  - `post_title` → webhook name
  - Post meta keys:
    - `td_webhook_enabled` (bool)
    - `td_webhook_url` (string)
    - `td_webhook_method` (`get|post|put|patch|delete`)
    - `td_webhook_request_format` (`form|json|xml`)
    - `td_webhook_headers` (array of `{ key, value }`)
    - `td_webhook_body_mapping` (array of `{ key, value }`, supports bracket keys)
    - `td_webhook_trigger_when` (`on_submit|on_success`)
    - `td_webhook_consent_required` (bool)
    - `td_webhook_targeting` (object `{ scope, form_ids, post_ids, slugs }`)
    - `td_webhook_advanced` (object `{ timeout, async, retry_policy }` – reserved for future)
- Logs: option `td_webhooks_logs` structure `{ [webhook_id]: [ LogEntry, ... ] }`
- Settings: option `td_webhooks_settings`:
  - `timeout` (seconds, default 8)
  - `retention_per_id` (default 100)
  - `ttl_days` (days, 0 disables)
  - `allowlist`, `denylist` (domain patterns)

## Triggers

- `on_submit`: listens to `tcb_api_form_submit` (raw sanitized POST context)
- `on_success`: listens to `thrive_core_lead_signup` (after successful subscription)

## Mapping and templating

- Body mapping array of `{ key, value }`
- Bracket notation builds nested structures: `user[name]` → `{ user: { name: ... } }`
- Placeholders: `{{path}}` resolved via dot-notation on context (e.g., `{{data.email}}`, `{{user.user_email}}`)

Placeholder reference by trigger:
- On Submit (`tcb_api_form_submit`):
  - `{{data.FIELD}}` for each form input name (e.g., `email`, `name`, `phone`)
  - `{{data._tcb_id}}` form settings id; `{{data.page_slug}}`; `{{data.post_id}}`
- On Success (`thrive_core_lead_signup`):
  - `{{data.email}}`, `{{data.first_name}}`, `{{data.last_name}}` (normalized lead data if present)
  - `{{user.user_email}}`, `{{user.user_login}}` (WP user details when available)

## HTTP sending

- Uses WP HTTP API
- Formats:
  - `json`: JSON body + `Content-Type: application/json`
  - `form`: default; array encoded
  - `xml`: simple XML serialization
- Headers merged from mapping; `Host`/`Content-Length` stripped
- Timeout default 8s (settings)

## Security

- Protocols allowed: `http`, `https`
- Block `localhost` and `127.0.0.1`
- Optional allowlist/denylist on host patterns
- Logs mask common secret header names
- Request/response bodies truncated to 2000 chars in logs

Consent behavior
- If `Require Consent` is enabled on the webhook and Trigger When is On Submit, the webhook only sends when the form’s `user_consent` or `gdpr` flag is truthy.
- On Success implies upstream consent checks have passed; we still honor `Require Consent` but it will typically be satisfied.

Targeting rules
- Scope `all`: no filtering
- Scope `include`: send only if at least one matches among Form IDs (`_tcb_id`), Post IDs, or Slugs
- Scope `exclude`: skip if any matches among the above

## REST API

- Namespace: `td/v1`

- Webhooks
  - `GET /webhooks` → list
  - `POST /webhooks` → create
  - `GET /webhooks/{id}` → read
  - `PUT /webhooks/{id}` → update
  - `DELETE /webhooks/{id}` → delete
  - `GET /webhooks/{id}/logs` → logs
  - `POST /webhooks/{id}/test` → send test using optional payload context

- Settings
  - `GET /webhooks/settings`
  - `PUT /webhooks/settings`

Auth: standard WP REST nonce + `TVE_DASH_CAPABILITY` capability.

Examples (curl)

Create a webhook
```bash
curl -X POST \
  -H "X-WP-Nonce: $(wp_create_nonce wp_rest)" \
  -H "Content-Type: application/json" \
  -b cookie.txt -c cookie.txt \
  --data '{
    "name":"My Hook",
    "enabled":true,
    "url":"https://webhook.site/xxx",
    "method":"post",
    "request_format":"json",
    "trigger_when":"on_submit",
    "headers":[{"key":"X-App","value":"TD"}],
    "body_mapping":[{"key":"email","value":"{{data.email}}"}],
    "targeting":{"scope":"all"}
  }' \
  http://site.test/wp-json/td/v1/webhooks
```

Update
```bash
curl -X PUT \
  -H "X-WP-Nonce: $(wp_create_nonce wp_rest)" \
  -H "Content-Type: application/json" \
  -b cookie.txt -c cookie.txt \
  --data '{"enabled":false}' \
  http://site.test/wp-json/td/v1/webhooks/123
```

Get logs
```bash
curl -H "X-WP-Nonce: $(wp_create_nonce wp_rest)" \
  -b cookie.txt -c cookie.txt \
  http://site.test/wp-json/td/v1/webhooks/123/logs
```

Test send
```bash
curl -X POST \
  -H "X-WP-Nonce: $(wp_create_nonce wp_rest)" \
  -H "Content-Type: application/json" \
  -b cookie.txt -c cookie.txt \
  --data '{"data":{"email":"john@example.com"}}' \
  http://site.test/wp-json/td/v1/webhooks/123/test
```

## Admin UI

Menu: Thrive Dashboard → Webhooks
- Tabs: All Webhooks, Add/Edit, Logs, Settings
- Simple repeaters for headers and body mapping

Capabilities
- Access requires `tve-use-td` (administrators have it by default). If menu is missing, verify capability.

## Programmatic usage

```php
use TVE\Dashboard\Webhooks\TD_Webhooks_Repository;

$id = TD_Webhooks_Repository::create([
    'name' => 'My Hook',
    'enabled' => true,
    'url' => 'https://example.com',
    'method' => 'post',
    'request_format' => 'json',
    'body_mapping' => [ [ 'key' => 'email', 'value' => '{{data.email}}' ] ],
    'trigger_when' => 'on_submit',
]);
```

Manual QA
- Create a webhook with JSON format and map `email` to `{{data.email}}`
- Add a TCB lead gen form with an Email field and submit
- Verify log entry status code and payload in Logs tab

Troubleshooting
- If menu doesn’t show: check capability `tve-use-td` and that `TVE\Dashboard\Webhooks\Main::init()` is called (see `thrive-dashboard.php`)
- If requests fail: check site can reach external URL, verify allowlist/denylist, and inspect Logs tab

## Files

- `inc/webhooks/class-main.php`: bootstrap (register, menu, options, REST)
- `inc/webhooks/class-td-webhooks-repository.php`: CPT CRUD
- `inc/webhooks/class-td-webhooks-dispatcher.php`: hook listeners + selection
- `inc/webhooks/class-td-webhooks-templating.php`: payload builder
- `inc/webhooks/class-td-webhooks-sender.php`: HTTP request + logging
- `inc/webhooks/class-td-webhooks-logger.php`: option ring-buffer logs
- `inc/webhooks/class-td-webhooks-validator.php`: validations
- `inc/webhooks/class-td-webhooks-admin.php`: admin screens
- `inc/webhooks/class-td-webhooks-rest-controller.php`: REST endpoints

## Versioning & compatibility
- Default timeout and retention are configurable; no DB tables created
- Module is included by `thrive-dashboard/thrive-dashboard.php` and initializes on admin init

