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
- Uses MySQL for development (schroeder247_admin database)
- Standard Laravel migration and seeding structure
- User factory and AdminUserSeeder included for seeding admin user

## Application Features

### Authentication System
- Laravel Breeze authentication with email/password login
- Public registration is disabled - admin-only user creation
- Admin user seeded: ryan@schroeder247.com / Changeme01!!!

### Monitor Management System
- Full CRUD operations for website/server monitors
- Support for HTTP/HTTPS, Ping, and TCP port monitoring
- Configurable check intervals, timeouts, and expected responses
- SSL certificate validation for HTTPS monitors
- Content verification for web pages
- User-specific monitors with authorization policies

### SMS Integration & AI Assistant
- Twilio webhook integration for bidirectional SMS
- AI assistant powered by OpenAI API for general queries
- System status queries directly from SMS (no API calls needed)
- Conversation history tracking in database
- Background job processing for SMS responses

### Queue System
- Redis-based queue processing for background jobs
- CheckMonitor job for individual monitor checks
- ProcessIncomingSMS job for AI responses
- Console command: `php artisan monitors:check-all`

### Dashboard
- Mobile-first responsive design
- Real-time monitor status overview
- Recent SMS conversation display
- Quick action buttons for common tasks

## Key Commands

### Monitoring System
- `php artisan monitors:check-all` - Check all enabled monitors and dispatch jobs
- Monitor checks run via CheckMonitor job dispatched from command
- Results stored in monitor_results table

### Queue Processing
- `php artisan queue:work` - Process queued jobs (included in dev command)
- Used for monitor checks and SMS processing

### Database Operations
- `php artisan migrate` - Run database migrations
- `php artisan db:seed --class=AdminUserSeeder` - Seed admin user