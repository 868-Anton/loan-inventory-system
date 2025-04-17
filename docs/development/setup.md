# Development Setup Guide

This guide will help you set up the Loan Inventory System for local development.

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 16+ and npm/yarn
- MySQL or SQLite
- Git

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/loan-inventory-system.git
cd loan-inventory-system
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install JavaScript Dependencies

```bash
npm install
# or
yarn install
```

### 4. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit the `.env` file and configure your database:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=loan_inventory
DB_USERNAME=root
DB_PASSWORD=
```

For SQLite, you can use:

```
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

### 5. Run Migrations and Seeders

```bash
# For SQLite, first create the file:
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed the database with initial data
php artisan db:seed
```

### 6. Start Development Servers

```bash
# Start the Laravel server
php artisan serve

# In another terminal, start Vite for frontend assets
npm run dev
# or
yarn dev
```

Or use the composite command:

```bash
composer dev
```

### 7. Access the Application

- Web interface: [http://localhost:8000](http://localhost:8000)
- Admin panel: [http://localhost:8000/admin](http://localhost:8000/admin)

Default admin credentials:
- Email: admin@example.com
- Password: password

## Working with Laravel Sail (Docker)

If you prefer to use Docker:

```bash
# Start Sail
./vendor/bin/sail up -d

# Run commands via Sail
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

## Development Workflow

1. Pull the latest changes from the main branch
2. Create a feature branch
3. Make your changes
4. Format code with Laravel Pint
5. Run tests
6. Submit a pull request

## Useful Commands

```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Run tests
php artisan test

# Generate IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:models
php artisan ide-helper:meta

# Clear cache
php artisan optimize:clear
```

## Troubleshooting

If you encounter any issues during setup, refer to the [Troubleshooting](./troubleshooting.md) document or open an issue on GitHub. 