# Project Requirements Document

## Project Name
**Loan Inventory System**

## Repository
[GitHub 
Repo](https://github.com/868-Anton/loan-inventory-system/tree/feature/category-items-view)

## Overview
This application manages the loaning and tracking 
of inventory items. It supports item 
categorization, loan creation with multiple 
items, condition tracking, and item status 
management.

## Tech Stack
- Laravel 10
- MySQL
- Tailwind CSS
- Blade templating
- GitHub for version control
- (Optional: Livewire or AlpineJS for 
interactivity)

## Functional Requirements

### Categories Page
- Display all categories with:
  - Category name
  - Number of items in the category (e.g., "23 • 
View")
  - Clickable link to view items in that category 
(`/categories/{id}/items`)
- Column visibility picker with drag-and-drop 
reordering
- Columns should be user-configurable (optional 
enhancement)

### Loan Form
- Multi-select input for adding items to a loan
- Searchable by name or serial number
- When an item is selected, limit selection to 
available quantity
- Prevent over-selection (e.g., no more than 15 
if only 15 are available)
- Capture condition before and after loan
- Generate a printable voucher/report upon loan 
submission

### Loan Deletion
- Soft delete loans
- On deletion, set all associated loaned items 
back to `available`
- Display confirmation alert explaining what 
deleting a loan entails

### Database Design
- `items` table has relationships to 
`categories`, `loans` via `loan_items` pivot
- `loan_items` pivot contains:
  - `item_id`, `loan_id`, `quantity`, 
`serial_numbers`, `condition_before`, 
`condition_after`, `status`, `timestamps`
- Ensure `status` references are unambiguous in 
joins

### Additional Features (Future Consideration)
- User roles and permissions
- Audit trail for item status/history
- Notifications/reminders for overdue loans

## Development Standards

### Folder Structure
- MVC pattern: Models, Controllers, Views cleanly 
separated
- Resource controllers used for standard CRUD
- Blade components for reusable UI

### Coding Standards
- Follows PSR-12 coding style
- Uses Eloquent relationships properly
- Keeps logic in controllers or services, not 
views

### Data Integrity
- Constraints enforced at the DB and application 
levels
- Refresh tables with seed data to test logic 
after migrations

---

## Implementation Guidelines for Cursor AI

When working in this repo, Cursor AI should 
always:
1. Follow the functional requirements listed 
above
2. Use Eloquent relationships instead of raw SQL 
where possible
3. Ensure logic aligns with Laravel conventions
4. Keep code modular and DRY
5. Assume each view and feature must be 
mobile-friendly (Tailwind)
---

## Filament Integration (Admin Panel Setup)

Filament is used as the admin panel builder in 
this Laravel project. It provides a clean 
interface for managing models like Loans, Items, 
Categories, and Borrowers.

### 📌 Why Filament?
- Built with TailwindCSS (matches the project’s 
styling)
- Auto-generates admin views for CRUD 
functionality
- Supports relationship management (e.g. Loan → 
Items)
- Easily extendable with custom actions, filters, 
and UI

### ✅ Resources to be Created
- `LoanResource`:
  - Create/edit loans with loan number, borrower, 
loan/return date
  - Repeater to select multiple items, quantity, 
and condition
- `ItemResource`:
  - Standard CRUD for items, including category 
relationship
- `CategoryResource`:
  - CRUD for item categories
- `BorrowerResource`:
  - Registered and guest borrower management

### 🚀 Admin Panel Access
- Route: `/admin`
- Login: Created via `php artisan 
make:filament-user`

### 💡 Optional
- Install Spatie Roles to restrict panel access 
by role (`admin`, `clerk`, etc.)
- Use Filament RelationManagers for deeper 
control of linked data (e.g., loan_items)

