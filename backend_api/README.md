# Rockport Records API

Laravel 13 REST API for the fictional Rockport Records dashboard. For full stack setup, MySQL Workbench configuration, demo credentials, seed sources, risk scoring, and endpoint documentation, see the repository [README](../README.md).

Quick start from this directory:

```powershell
Copy-Item .env.example .env
composer update
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Before migration, create the `rockport_records` schema in MySQL Workbench and set the connection values in `.env`. The initial `composer update` resolves Sanctum against the Laravel 13 project dependencies and refreshes the inherited lock file.
