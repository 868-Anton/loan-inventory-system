# Project Requirements Document

## Project Name
**Loan Inventory System**

## Repository
[GitHub Repo](https://github.com/868-Anton/loan-inventory-system/tree/feature/category-items-view)

---

## Overview
This application manages the loaning and tracking of inventory items. It supports:

- Item categorization and serial tracking
- Loan creation with multi-item selection
- Condition monitoring (before/after loan)
- Borrower management (registered & guest)
- Inventory reporting and printable vouchers
- User roles with permissions
- Column customization and UI quality-of-life features

---

## Tech Stack
- **Backend**: Laravel 10
- **Database**: MySQL
- **Frontend**: Tailwind CSS + Blade templates
- **Admin Panel**: Filament
- **Version Control**: GitHub
- **(Optional)**: Livewire or AlpineJS for interactivity

---

## Functional Requirements

### 📁 Categories Page
- Show all categories with:
  - Category name
  - Number of items (e.g., `23 • View`)
  - Clickable link: `/categories/{id}/items`
- Column visibility picker
- Drag-and-drop column reordering

---

### 📦 Items
- Items belong to categories
- Items have quantity, serial numbers, and status
- Status: available, loaned, damaged, lost
- Track condition (before/after use)

---

### 🧾 Loan Form
- Multi-select searchable input for items (by name or serial)
- Enforce quantity limits: can't borrow more than available
- Capture condition before and after
- Auto-generate printable loan voucher/report upon submission

---

### 🔁 Loan Return Flow
- Interface to process returned items
- Update condition and return status
- Revert item status to `available` or `damaged`/`lost`

---

### 🧍 Borrowers
- Search for existing borrower by name/ID
- If not found, register a new **guest** borrower
- Store borrower type: `registered` or `guest`
- Clerk role handles borrower creation & assignment

---

### 🗑️ Loan Deletion
- Soft delete loans
- Automatically mark associated items as `available`
- Display confirmation warning before delete

---

### 📄 Reports
- Filter loans by borrower, date, or item
- Summaries of damaged or missing items
- Export loan history and inventory to PDF or Excel
- View borrower loan activity log

---

### 🖨️ Loan Voucher (Printable)
- After loan submission, generate a printable voucher
- Include:
  - Borrower name and type
  - List of items and serials
  - Loan date and conditions
- Output as PDF or print-ready HTML

---

## 🧑‍💼 User Roles & Permissions

| Role      | Permissions                                                                 |
|-----------|------------------------------------------------------------------------------|
| Admin     | Full access: manage users, categories, items, loans, and reports            |
| Clerk     | Can create/edit loans, register borrowers, generate reports (no deletes)    |
| Viewer    | Read-only access to items, categories, and loan data                        |

---

## 🗂️ Database Design Overview

- `categories` → hasMany `items`
- `items` → belongsTo `categories`
- `loans` → belongsTo `borrowers`
- `loan_items` (pivot) → connects `items` and `loans`

### `loan_items` pivot table fields:
- `item_id`, `loan_id`
- `quantity`, `serial_numbers`
- `condition_before`, `condition_after`
- `status` (available, loaned, returned)
- `timestamps`

> ⚠️ Ensure SQL queries avoid ambiguous column names like `status` in joins.

---

## 🚦 Feature Timeline Plan

### ✅ Phase 1 – Core Inventory & Loans *(Completed)*
- [x] Category & Item CRUD
- [x] Loan form with item selection
- [x] Quantity & serial validation
- [x] Condition tracking logic
- [x] Soft delete loan resets item status

### 🔄 Phase 2 – Roles, Borrowers & Reports *(In Progress)*
- [ ] Add roles and permission restrictions
- [ ] Borrower search and guest registration
- [ ] Filter/search loans by borrower/item
- [ ] Export/print reports

### 🔧 Phase 3 – UI & UX Enhancements *(Pending)*
- [ ] Column visibility picker
- [ ] Drag-and-drop column reordering
- [ ] Reusable Blade components
- [ ] Demo seeders for test data

---

## 🔧 Development Standards

### Folder Structure
- Follow MVC:
  - `Models/`, `Http/Controllers/`, `resources/views/`
- Use resource controllers for CRUD
- Use Blade components for reusable UI pieces

### Code Practices
- Follow PSR-12 coding style
- Prefer Eloquent relationships over raw SQL
- No business logic inside Blade views

### Data Integrity
- Use seeders for consistent test data
- Enforce constraints in DB and validation in code

---

## ✅ Final Notes
This document should guide all development, testing, and validation efforts. All PRs and feature branches should reference the applicable section from this PRD.

