# Thrive Apprentice

Thrive Apprentice gives you the most flexible drag and drop WordPress course building solution on the market — alongside a complete online business building toolkit!

## Requirements
* Composer - [info here](https://getcomposer.org/download/)
* NodeJS - [info here](https://nodejs.org/)

## After checkout from git

We use composer for autoload setup
```bash
composer update && composer install
```

We use node for installing dependencies in our current project
```bash
npm install
```

We need to make 3 symlinks:
1. [thrive-dashboard](https://github.com/ThriveThemes/thrive-dashboard) project under `thrive-dashboard` folder name
2. [tcb](https://github.com/ThriveThemes/tcb) project under `tcb` folder name
3. [thrive-theme](https://github.com/ThriveThemes/thrive-theme) project under `builder` folder name



## Other
* `composer dump-autoload` for regenerating the autoload files

See `package.json` for running additional scripts

## For developing:
`npm run watch` for developing. This command watches every modification on asset files (*.js, *.scss) and generate the corresponding (*.js..min, *.css) files

For additional details please see `webpack.config.js` file

Make sure you have the following constants in `wp-config.php` file

```
define( 'WP_DEBUG', false );
define( 'TCB_TEMPLATE_DEBUG', true );
define( 'THRIVE_THEME_CLOUD_DEBUG', true );
define( 'TCB_CLOUD_DEBUG', true );
define( 'TL_CLOUD_DEBUG', true );
define( 'TVE_DEBUG', true );`
```

## Testing

Tests live in `tools/tests/thrive-apprentice/` (not shipped with the plugin).

### Prerequisites

- PHP 8.1+ with the `mysqli` extension
- MySQL server (any source: Local by Flywheel, Homebrew, Docker, MAMP, etc.)
- Composer
- A WordPress site with Thrive plugins installed (for `WP_CONTENT_DIR`)

### One-time setup

1. Navigate to the test directory and copy the example env file:

```bash
cd tools/tests/thrive-apprentice
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
- **Local by Flywheel:** Open Local, select your site, go to the **Database** tab - the socket path is listed there.
- **Alternatively:** Run this command in the WordPress Shell - `php -r "echo ini_get('mysqli.default_socket');"`

3. Run the setup script:

```bash
bash bin/setup-tests.sh
```

This downloads PHPUnit, installs Composer dev dependencies, sets up the WordPress test library, creates the test database, and configures `wp-tests-config.php`.

### Running tests

```bash
cd tools/tests/thrive-apprentice
composer test         # all test suites
composer test:api     # API tests only
```

### Notes

- The setup script automatically loads `.env.testing` — no need to `source` it manually.
- `.env.testing` is gitignored (contains machine-specific paths).
- `.env.testing.example` is committed with default values and instructions.
- The test database (`wp_tests` by default) is **dropped and recreated on every test run** — never point it at a production database.

![TA unit tests](https://github.com/ThriveThemes/thrive-apprentice/workflows/TA%20unit%20tests/badge.svg)
