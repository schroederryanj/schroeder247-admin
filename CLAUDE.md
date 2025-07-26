# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application (schroeder247-admin) with a modern frontend stack using Vite and Tailwind CSS 4. The project follows standard Laravel conventions and includes both PHP backend and JavaScript frontend development tools.

## Common Development Commands

### Development Server
- `composer run dev` - Starts all development services (Laravel server, queue worker, logs, and Vite dev server)
- `php artisan serve` - Start Laravel development server only
- `npm run dev` - Start Vite development server for frontend assets

### Building and Assets
- `npm run build` - Build production assets with Vite
- `php artisan config:clear` - Clear Laravel configuration cache

### Testing
- `composer run test` - Run the full test suite (clears config and runs PHPUnit)
- `php artisan test` - Run tests directly with Artisan
- Individual test files are in `tests/Feature/` and `tests/Unit/`

### Code Quality
- Laravel Pint is available for code formatting (included in composer.json)
- PHPUnit 11.5+ is configured for testing

## Architecture and Structure

### Backend (Laravel)
- **Controllers**: `app/Http/Controllers/` - Standard Laravel MVC controllers
- **Models**: `app/Models/` - Eloquent models (User model included)
- **Providers**: `app/Providers/` - Service providers (AppServiceProvider configured)
- **Database**: Uses SQLite by default (`database/database.sqlite`)
- **Migrations**: `database/migrations/` - Standard Laravel migration structure
- **Routes**: `routes/web.php` for web routes, `routes/console.php` for console commands

### Frontend
- **Vite Configuration**: Uses Laravel Vite plugin with Tailwind CSS 4
- **Entry Points**: `resources/css/app.css` and `resources/js/app.js`
- **Views**: Blade templates in `resources/views/`
- **Assets**: Built assets served from `public/` directory

### Key Configuration Files
- `composer.json` - PHP dependencies and custom scripts
- `package.json` - Node.js dependencies and build scripts
- `vite.config.js` - Vite build configuration with Laravel and Tailwind plugins
- `phpunit.xml` - PHPUnit testing configuration
- `config/` directory contains Laravel configuration files

### Development Workflow
The project is set up for concurrent development with the `composer run dev` command that starts:
- Laravel development server
- Queue worker
- Laravel Pail for logs
- Vite dev server for hot module replacement

### Database
- Uses SQLite for development (database/database.sqlite)
- Standard Laravel migration and seeding structure
- User factory and seeder included