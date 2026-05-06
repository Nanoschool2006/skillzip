# Testing

Tests live in `tools/tests/thrive-ultimatum/` (not shipped with the plugin).

## Prerequisites

- PHP 8.1+ with the `mysqli` extension
- MySQL server (Local by Flywheel, Homebrew, Docker, MAMP, etc.)
- A WordPress site with Thrive plugins installed (for `WP_CONTENT_DIR`)

## One-time setup

1. Navigate to the test directory and copy the example env file:

```bash
cd tools/tests/thrive-ultimatum
cp .env.testing.example .env.testing
```

2. Edit `.env.testing` — uncomment and set `DB_HOST` and `WP_CONTENT_DIR`:

```bash
# MySQL socket or TCP host
DB_HOST="localhost:/path/to/mysqld.sock"

# wp-content directory with Thrive plugins
WP_CONTENT_DIR="/path/to/wp-content"
```

To find your MySQL socket:
- **Local by Flywheel:** Open Local, select your site, go to the **Database** tab — the socket path is listed there.
- **Alternatively:** `php -r "echo ini_get('mysqli.default_socket');"`

3. Run the setup script:

```bash
bash bin/setup-tests.sh
```

This downloads PHPUnit, installs Composer dev dependencies, verifies the WordPress test library, creates the test database, and configures `wp-tests-config.php`.

## Running tests

```bash
cd tools/tests/thrive-ultimatum
composer test         # all test suites
composer test:api     # API tests only
```

## Notes

- The setup script automatically loads `.env.testing` — no need to `source` it manually.
- `.env.testing` is gitignored (contains machine-specific paths).
- `.env.testing.example` is committed with default values and instructions.
- The test database (`wp_tests` by default) is **dropped and recreated on every test run** — never point it at a production database.
