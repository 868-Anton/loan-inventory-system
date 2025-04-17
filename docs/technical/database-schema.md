# Database Schema

This document describes the database schema for the Loan Inventory System.

## Entity Relationship Diagram

![Entity Relationship Diagram](../assets/images/erd.png)

## Tables

### Users

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | User's full name |
| email | string | User's email address (unique) |
| email_verified_at | timestamp | When email was verified |
| password | string | Hashed password |
| department_id | bigint (foreign key) | Associated department |
| remember_token | string | Token for "remember me" functionality |
| created_at | timestamp | When record was created |
| updated_at | timestamp | When record was last updated |

### Departments

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Department name |
| description | text | Department description |
| location | string | Physical location |
| contact_email | string | Contact email |
| created_at | timestamp | When record was created |
| updated_at | timestamp | When record was last updated |
| deleted_at | timestamp | When record was soft deleted |

### Categories

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Category name |
| description | text | Category description |
| parent_id | bigint (self-referencing) | Parent category ID |
| attributes | json | Custom attributes schema |
| created_at | timestamp | When record was created |
| updated_at | timestamp | When record was last updated |
| deleted_at | timestamp | When record was soft deleted |

### Items

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Item name |
| description | text | Item description |
| thumbnail | string | Path to thumbnail image |
| serial_number | string | Unique serial number |
| asset_tag | string | Unique asset tag |
| purchase_date | date | When item was purchased |
| purchase_cost | decimal | Purchase cost |
| warranty_expiry | date | Warranty expiration date |
| status | enum | Item status (available, borrowed, overdue, under_repair, lost) |
| category_id | bigint (foreign key) | Associated category |
| custom_attributes | json | Custom attributes for this item |
| created_at | timestamp | When record was created |
| updated_at | timestamp | When record was last updated |
| deleted_at | timestamp | When record was soft deleted |

### Loans

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| loan_number | string | Unique loan identifier |
| user_id | bigint (foreign key) | User who created the loan |
| department_id | bigint (foreign key) | Associated department |
| is_guest | boolean | Whether borrower is a guest |
| guest_name | string | Guest's name (if applicable) |
| guest_email | string | Guest's email (if applicable) |
| guest_phone | string | Guest's phone (if applicable) |
| guest_id | string | Guest's ID (if applicable) |
| loan_date | date | When items were loaned |
| due_date | date | When items are due |
| return_date | date | When items were returned |
| notes | text | Additional notes |
| status | enum | Loan status (pending, active, overdue, returned, canceled) |
| signature | string | Digital signature |
| voucher_path | string | Path to voucher file |
| created_at | timestamp | When record was created |
| updated_at | timestamp | When record was last updated |
| deleted_at | timestamp | When record was soft deleted |

### Loan Items (Pivot)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| loan_id | bigint (foreign key) | Associated loan |
| item_id | bigint (foreign key) | Associated item |
| condition_before | string | Item condition before loan |
| condition_after | string | Item condition after return |
| status | string | Status of this item in the loan |
| created_at | timestamp | When record was created |
| updated_at | timestamp | When record was last updated |

## Relationships

- **User** belongs to one **Department**
- **Department** has many **Users**
- **Department** has many **Loans**
- **Category** has many **Items**
- **Category** may have a parent **Category**
- **Category** may have many child **Categories**
- **Item** belongs to one **Category**
- **Loan** belongs to one **User** (creator)
- **Loan** belongs to one **Department**
- **Loan** belongs to many **Items** (through **Loan Items**)
- **Item** belongs to many **Loans** (through **Loan Items**) 