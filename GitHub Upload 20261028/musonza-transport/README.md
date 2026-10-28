# Musonza Transport & Consultancy Website

Laravel source for the Musonza Transport & Consultancy website.

## Local setup

1. Install the PHP and Composer versions required by composer.json.
2. Run composer install.
3. Copy .env.example to .env and configure the environment values.
4. Run php artisan key:generate.
5. Configure the database, then run php artisan migrate.
6. Start locally with php artisan serve.

The repository intentionally excludes local environment secrets, the local SQLite database, generated caches, logs, and Composer's vendor directory. Set production credentials and database settings in the hosting provider's environment configuration.
