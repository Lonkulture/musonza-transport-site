# Musonza Transport & Consultancy Website

Laravel 13 source for the Musonza Transport & Consultancy website.

## Render preview

This repository includes a Docker setup and Render Blueprint. In Render, create a new Blueprint from this repository and select the free web service. The Blueprint creates a PHP 8.3 / Apache service and generates the Laravel application key.

The free preview uses SQLite on Render's temporary filesystem. It is suitable for viewing and demonstrations, not for keeping customer enquiries. Render's free web services can sleep when idle, and local filesystem data can be lost when a service sleeps, restarts, or redeploys. The configured mailer logs messages; it does not send email. Before using the site for real customer enquiries, configure a persistent database, outbound email, and an admin account.

The service listens on Render's PORT environment variable. The database schema is migrated on each service start.