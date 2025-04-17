# Coding Standards

This document outlines the coding standards and best practices for the Loan Inventory System project.

## General PHP Standards

- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting
- Use type declarations for method parameters and return types
- Use strict typing where appropriate (`declare(strict_types=1)`)
- Limit line length to 120 characters
- Use meaningful variable and method names
- Avoid abbreviations unless widely understood

## Laravel Best Practices

### General

- Follow Laravel's naming conventions
- Keep controllers thin; use Form Requests, Services, and Actions
- Use dependency injection instead of facades when appropriate
- Use value objects for complex value types
- Use configuration files for application configuration
- Extract reusable code into traits, concerns, or helpers

### Routing

- Use resourceful routing when appropriate
- Group related routes
- Name all routes
- Use route model binding when dealing with models
- Use middleware groups for common middleware combinations

### Controllers

- Use single action controllers for complex actions
- Keep controller methods small and focused
- Use Form Requests for validation
- Use API resources for API responses
- Return appropriate HTTP status codes

### Models

- Define relationships in models
- Use accessors and mutators for data transformation
- Use Eloquent scopes for reusable query constraints
- Define fillable or guarded properties
- Use proper type casting
- Use observers for complex model events

### Validation

- Use Form Requests for complex validation
- Define custom validation rules as classes
- Validate early, fail fast

### Database

- Always define foreign keys in migrations
- Always define indexes in migrations
- Use migrations for database changes
- Use seeders for test data
- Avoid raw queries; use Eloquent instead

## Filament Specific Standards

### Resources

- Group related fields using sections, fieldsets, or tabs
- Use appropriate field types
- Add validation rules directly to fields
- Keep resource forms clean and organized
- Implement filters, search, and sorting for tables
- Use custom headers and footers for complex tables
- Leverage Filament actions for CRUD operations and more

### Pages

- Create custom pages for non-resource operations
- Use page data for passing data to the page
- Organize related functionality into page actions

### Forms

- Group related fields using components
- Use hints and help text for complex fields
- Use field requirements and dependencies
- Validate at the field level when possible
- Use custom form components for reusable forms

### UI Components

- Use Filament UI components instead of raw HTML
- Create custom components for reusable UI elements
- Follow Tailwind CSS naming conventions
- Use responsive variants for different screen sizes

## Front-end Standards

### Tailwind CSS

- Use Tailwind's utility classes instead of custom CSS
- Extract common patterns to components
- Use responsive variants for different screen sizes
- Follow mobile-first approach

### JavaScript

- Use Alpine.js for interactive components
- Keep Alpine.js components small and focused
- Use ES6+ syntax
- Document complex functions
- Avoid jQuery when possible

## Testing Standards

- Write meaningful tests
- Test edge cases
- Use factories for test data
- Use test databases
- Mock external dependencies

## Enforcement

- Use Laravel Pint for code formatting
- Use PHPStan or Larastan for static analysis
- Review code for adherence to standards
- Set up CI/CD pipelines to enforce standards 