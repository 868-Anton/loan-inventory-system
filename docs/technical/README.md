# Technical Documentation

This section contains technical documentation for the Loan Inventory System.

## Contents

- [System Architecture](./architecture.md)
- [Database Schema](./database-schema.md)
- [Authentication & Authorization](./auth.md)
- [Filament Admin Implementation](./filament.md)
- [Class Diagrams](./class-diagrams.md)
- [Design Patterns](./design-patterns.md)

## System Overview

The Loan Inventory System is built on Laravel 12 with Filament 3 for the admin interface. It uses a MySQL/SQLite database for storage and implements proper model relationships for efficient data access.

## Key Technical Features

- Eloquent ORM for database interactions
- Filament 3 for admin panel interfaces
- Soft deletions for data preservation
- Laravel's built-in authentication
- Form requests for validation 