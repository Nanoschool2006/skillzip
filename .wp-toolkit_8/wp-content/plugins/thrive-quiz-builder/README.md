# Thrive Quiz Builder

Thrive Quiz Builder not only gives you the ability to create extremely complex quizzes with branching logic, it also makes it extremely easy to visualize what your quiz looks like and how it flows in our quiz builder window.

## Requirements
* NodeJS - [info here](https://nodejs.org/)

We need to make 2 symlinks:
1. [thrive-dashboard](https://github.com/ThriveThemes/thrive-dashboard) project under `thrive-dashboard` folder name
2. [tcb](https://github.com/ThriveThemes/tcb) project under `tcb` folder name

## Instalation
* Checkout from git into `/wp-content/plugins` folder
* from terminal execute `npm install` in the main project folder `thrive-quiz-builder`
* `cd graph-editor` and execute `npm install`
* `cd image-editor` and execute `npm install`

## Other
* `npm run watch` for compiling javascript and style files and listening to changes. Should be executed in all 3 folders `thrive-quiz-builder`, `graph-editor`, `image-editor`. For additional details please see `webpack.config.js` file

See `package.json` for running additional scripts

Make sure you have the following constants in `wp-config.php` file

```
define( 'WP_DEBUG', true );
define( 'TCB_TEMPLATE_DEBUG', true );
define( 'THRIVE_THEME_CLOUD_DEBUG', true );
define( 'TCB_CLOUD_DEBUG', true );
define( 'TL_CLOUD_DEBUG', true );
define( 'TVE_DEBUG', true );`
```

## Testing

### Prerequisites

- PHP 8.1+ with the `mysqli` extension
- MySQL server (Local by Flywheel, Homebrew, Docker, MAMP, etc.)
- A WordPress site with Thrive plugins installed (for `WP_CONTENT_DIR`)

### One-time setup

1. Copy the example env file and fill in your local values:

```bash
cp tools/tests/thrive-quiz-builder/.env.testing.example tools/tests/thrive-quiz-builder/.env.testing
```

2. Edit `tools/tests/thrive-quiz-builder/.env.testing` — uncomment and set `DB_HOST` and `WP_CONTENT_DIR`:

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
bash tools/tests/thrive-quiz-builder/bin/setup-tests.sh
```

This downloads PHPUnit, verifies the WordPress test library, creates the test database, and configures `wp-tests-config.php`.

### Running tests

```bash
cd tools/tests/thrive-quiz-builder
source .env.testing && php bin/phpunit.phar --configuration phpunit.xml.dist --testdox
```

### Notes

- `tools/tests/thrive-quiz-builder/.env.testing` is gitignored (contains machine-specific paths).
- `tools/tests/thrive-quiz-builder/.env.testing.example` is committed with default values and instructions.
- The test database (`wp_tests` by default) is **dropped and recreated on every test run** — never point it at a production database.
