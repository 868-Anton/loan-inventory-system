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
- **Categories**: Hierarchical category system with parent/child relationships and smart sorting
- **Custom Attributes**: Flexible metadata for items
- **Status Tracking**: Item availability and condition monitoring
- **Quick Item Creation**: Add items directly from category pages with pre-filled category selection

### 2. Loan System
- **Dual Borrower Types**: Supports both registered users and guest borrowers
- **Polymorphic Relations**: Flexible borrower system (User or GuestBorrower)
- **Individual Item Tracking**: Each item instance tracked separately with detailed condition monitoring
- **Automatic Due Dates**: Defaults to 1 month from loan date
- **Digital Signatures**: Loan documentation with signatures
- **PDF Vouchers**: Automatically generated loan vouchers
- **Advanced Return Tracking**: Item-level condition assessment with return notes and condition tags

### 3. User Management
- **Department Integration**: Users linked to departments
- **Role-based Access**: Different user types and permissions
- **Soft Deletes**: Maintains user history
- **Guest Borrowers**: Separate entity for non-registered borrowers

### 4. Admin Interface (Filament)
- **Complete CRUD**: For all entities (Users, Items, Categories, Loans, LoanItems, etc.)
- **Rich Forms**: Advanced form components with validation and smart pre-filling
- **Data Tables**: Sortable, filterable tables with bulk actions and intelligent sorting
- **Relationship Management**: Easy handling of related data
- **Smart Category Sorting**: Categories automatically sorted by borrowed/available item counts
- **Enhanced Item Management**: Comprehensive LoanItem resource for detailed item-level tracking

### 5. Public Interface
- **Item Catalog**: Public browsing of available items
- **Category Navigation**: Browse items by category with enhanced filtering
- **Item Details**: Modal views for item information
- **Quick Loan Creation**: Direct links from items to loan creation
- **Category-Based Item Management**: Add items directly from category pages with streamlined workflow

## Database Schema

The system includes these main entities:
- **Users** (with department associations)
- **Items** (with categories, serial numbers, status)
- **Categories** (hierarchical structure with smart sorting)
- **Loans** (polymorphic borrower relations)
- **LoanItems** (individual item tracking in loans with condition monitoring)
- **GuestBorrowers** (for non-registered users)
- **Departments** (organizational structure)

## Advanced Features

- **Soft Deletes**: Maintains data integrity across all models
- **Polymorphic Relationships**: Flexible borrower system
- **Individual Item Tracking**: Each item instance managed separately with detailed condition monitoring
- **Custom Fields**: Extensible metadata system
- **Automated Workflows**: Due date calculation, voucher generation
- **Advanced Condition Tracking**: Item-level condition assessment with return notes, condition tags, and timestamps
- **Smart Sorting**: Categories automatically sorted by borrowed/available item counts for better inventory management
- **Streamlined Workflows**: Quick item creation from category pages with intelligent form pre-filling
- **Enhanced Return Processing**: Comprehensive item-level return tracking with condition assessment

## Recent Improvements

### 🎯 Enhanced Category Management
- **Smart Category Sorting**: Categories are now automatically sorted by borrowed items count (descending), then by available items count (descending)
- **Quick Item Creation**: Add items directly from category pages with the "Add Item to Category" button
- **Intelligent Form Pre-filling**: Category field automatically populated when creating items from category context
- **Seamless Navigation**: Smart redirects return users to the category items list after item creation

### 🔍 Advanced Loan Item Tracking
- **Comprehensive LoanItem Resource**: Full CRUD interface for managing individual loan items
- **Condition Assessment**: Track item condition before and after loans with detailed notes
- **Return Processing**: Enhanced return workflow with condition tags and assessment timestamps
- **Item-Level Management**: Detailed tracking of each item's loan history and condition changes

### 📊 Improved User Experience
- **Efficient Workflows**: Streamlined processes for adding items and managing loans
- **Better Inventory Visibility**: Categories sorted by activity help identify high-demand equipment
- **Enhanced Data Management**: Comprehensive tracking of item conditions and return status
- **Smart Defaults**: Intelligent form pre-filling reduces manual data entry

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
- **Items and Categories**: Complete inventory management with smart sorting and quick item creation
- **Loans and LoanItems**: Advanced loan tracking with item-level condition monitoring
- **Users and Departments**: User management with department associations
- **Guest Borrowers**: Separate management for non-registered borrowers
- **System Settings**: Comprehensive system configuration

### Public Interface
- **Browse Items**: View all available items at `/items`
- **Category Views**: Explore items by category at `/categories/{category}/items`
- **Quick Actions**: Create loans directly from item pages
- **Enhanced Filtering**: Filter items by availability status (borrowed/available)

## File Structure

```
app/
├── Filament/           # Admin panel configuration
│   ├── Resources/      # Filament resources for all entities
│   └── Pages/          # Custom admin pages
├── Http/               # Controllers and middleware
├── Models/             # Eloquent models with advanced relationships
├── Services/           # Business logic services
└── ...

resources/
├── views/              # Blade templates with enhanced UI
├── css/                # Stylesheets
└── js/                 # JavaScript assets

database/
├── migrations/         # Database migrations with condition tracking
├── seeders/           # Database seeders
└── factories/         # Model factories
```

## Technical Highlights

### 🚀 Performance Optimizations
- **Efficient Queries**: Uses `withCount()` for optimized category sorting without N+1 queries
- **Smart Caching**: Intelligent caching strategies for better performance
- **Optimized Relationships**: Efficient polymorphic relationships for flexible borrower system

### 🔧 Advanced Features
- **Condition Tracking**: Comprehensive item-level condition monitoring with timestamps
- **Smart Sorting**: Dynamic category sorting based on real-time item counts
- **Intelligent Forms**: Context-aware form pre-filling for improved user experience
- **Enhanced Navigation**: Smart redirects maintain user context throughout workflows

## Contributing

This project follows Laravel coding standards and best practices. Please ensure:
- Proper PHPDoc documentation
- Database migrations for schema changes
- Tests for new functionality
- Consistent code formatting

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
