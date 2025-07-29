# Loan Management System

A comprehensive Laravel-based loan management system designed for organizations to efficiently track equipment loans, manage inventory, and handle both internal staff and guest borrowers.

## Project Overview

This is a **production-ready loan management system** that provides complete lifecycle management for equipment loans - from item registration to return processing. The system features a modern admin interface built with Filament and a public-facing catalog for easy item browsing.

## Core Technology Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Admin Panel**: Filament 3.0 (modern Laravel admin interface)
- **Frontend**: Vite + TailwindCSS 4.0 with custom views
- **Database**: MySQL (with SQLite support)
- **PDF Generation**: DomPDF for vouchers/reports
- **Authentication**: Laravel Breeze

## Key Features & Functionality

### 1. Inventory Management
- **Items**: Complete item tracking with serial numbers, asset tags, purchase info
- **Categories**: Hierarchical category system with parent/child relationships
- **Custom Attributes**: Flexible metadata for items
- **Status Tracking**: Item availability and condition monitoring

### 2. Loan System
- **Dual Borrower Types**: Supports both registered users and guest borrowers
- **Polymorphic Relations**: Flexible borrower system (User or GuestBorrower)
- **Individual Item Tracking**: Each item instance tracked separately
- **Automatic Due Dates**: Defaults to 1 month from loan date
- **Digital Signatures**: Loan documentation with signatures
- **PDF Vouchers**: Automatically generated loan vouchers

### 3. User Management
- **Department Integration**: Users linked to departments
- **Role-based Access**: Different user types and permissions
- **Soft Deletes**: Maintains user history
- **Guest Borrowers**: Separate entity for non-registered borrowers

### 4. Admin Interface (Filament)
- **Complete CRUD**: For all entities (Users, Items, Categories, Loans, etc.)
- **Rich Forms**: Advanced form components with validation
- **Data Tables**: Sortable, filterable tables with bulk actions
- **Relationship Management**: Easy handling of related data

### 5. Public Interface
- **Item Catalog**: Public browsing of available items
- **Category Navigation**: Browse items by category
- **Item Details**: Modal views for item information
- **Quick Loan Creation**: Direct links from items to loan creation

## Database Schema

The system includes these main entities:
- **Users** (with department associations)
- **Items** (with categories, serial numbers, status)
- **Categories** (hierarchical structure)
- **Loans** (polymorphic borrower relations)
- **LoanItems** (individual item tracking in loans)
- **GuestBorrowers** (for non-registered users)
- **Departments** (organizational structure)

## Advanced Features

- **Soft Deletes**: Maintains data integrity across all models
- **Polymorphic Relationships**: Flexible borrower system
- **Individual Item Tracking**: Each item instance managed separately
- **Custom Fields**: Extensible metadata system
- **Automated Workflows**: Due date calculation, voucher generation
- **Condition Tracking**: Item condition notes and return status
- **Sort Ordering**: Custom ordering for categories, items, users

## Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & npm
- MySQL or SQLite

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd loan-management-system
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   # Create database (MySQL)
   php database/create_db.php
   
   # Run migrations
   php artisan migrate
   
   # Seed database (optional)
   php artisan db:seed
   ```

5. **Build assets**
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

6. **Create admin user**
   ```bash
   php artisan make:filament-user
   ```

## Development

### Running the application
```bash
# Start all services (server, queue, logs, vite)
composer run dev

# Or individually:
php artisan serve
php artisan queue:work
npm run dev
```

### Testing
```bash
composer run test
```

## Usage

### Admin Panel
Access the admin panel at `/admin` to manage:
- Items and categories
- Users and departments
- Loans and returns
- Guest borrowers
- System settings

### Public Interface
- Browse items at `/items`
- View categories at `/categories/{category}/items`
- Create loans directly from item pages

## File Structure

```
app/
├── Filament/           # Admin panel configuration
├── Http/               # Controllers and middleware
├── Models/             # Eloquent models
├── Services/           # Business logic services
└── ...

resources/
├── views/              # Blade templates
├── css/                # Stylesheets
└── js/                 # JavaScript assets

database/
├── migrations/         # Database migrations
├── seeders/           # Database seeders
└── factories/         # Model factories
```

## Contributing

This project follows Laravel coding standards and best practices. Please ensure:
- Proper PHPDoc documentation
- Database migrations for schema changes
- Tests for new functionality
- Consistent code formatting

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
