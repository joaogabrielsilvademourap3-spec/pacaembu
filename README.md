# Agency OS

Agency OS is a Laravel-oriented full-stack application blueprint for managing social media and web design agency operations.

## Current environment limitation

This repository was initialized in an offline execution environment where package installation from Packagist/GitHub is blocked (HTTP 403 CONNECT tunnel), so a full Laravel runtime could not be installed automatically.

To keep progress moving, this commit provides the **core application scaffold** expected by Laravel projects:

- domain models and relationships
- enums for statuses and categories
- database migrations for all required modules
- initial route structure
- service class stubs for AI and reports
- architecture and rollout notes

## Next step in a connected environment

1. `composer create-project laravel/laravel .`
2. Merge the scaffolded files from this repository.
3. Install auth starter:
   - `composer require laravel/breeze --dev`
   - `php artisan breeze:install blade`
4. Run migrations and seeders:
   - `php artisan migrate --seed`
5. Continue with controllers, policies, Blade pages, tests, queue workers and notifications.

## Scope delivered in this commit

This is **Phase 1** (structure, schema, relationships). It intentionally sets up the foundation requested in your instructions: starting with Laravel structure, migrations, models, and relationships before building out each module workflow.
