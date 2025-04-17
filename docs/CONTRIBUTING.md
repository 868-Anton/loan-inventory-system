# Contributing to Loan Inventory System

Thank you for considering contributing to the Loan Inventory System! This document outlines the process for contributing to the project.

## Code of Conduct

By participating in this project, you agree to abide by our Code of Conduct. Please read it before contributing.

## Getting Started

1. Fork the repository
2. Clone your fork to your local machine
3. Set up your development environment following the [Setup Guide](./development/setup.md)
4. Create a new branch for your changes
5. Make your changes
6. Commit your changes
7. Push to your fork
8. Submit a pull request

## Development Process

### Branching Strategy

- `main` - Production-ready code
- `develop` - Development branch for integration
- Feature branches - Named as `feature/your-feature-name`
- Bugfix branches - Named as `fix/issue-description`

Always branch from `develop` and submit pull requests back to `develop`.

### Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

Types include:
- `feat`: A new feature
- `fix`: A bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code changes that neither fix bugs nor add features
- `test`: Adding or updating tests
- `chore`: Changes to the build process or auxiliary tools

### Pull Requests

1. Update your feature branch with the latest changes from `develop`
2. Ensure your code passes all tests
3. Format your code with Laravel Pint
4. Submit a pull request to the `develop` branch
5. Include a clear description of the changes
6. Reference any related issues

## Coding Standards

Please follow the [Coding Standards](./development/coding-standards.md) when contributing code.

## Testing

All new features should include tests. Run the test suite with:

```bash
php artisan test
```

Make sure all tests pass before submitting your pull request.

## Documentation

Update documentation as needed:

- Code comments for complex logic
- README updates for new features
- Wiki updates if applicable
- Update the changelog

## Reporting Bugs

When reporting bugs, please include:

- A clear, descriptive title
- Steps to reproduce the issue
- Expected behavior
- Actual behavior
- Screenshots if applicable
- Environment information (OS, browser, PHP version, etc.)

## Feature Requests

Feature requests are welcome. Please include:

- A clear, descriptive title
- Detailed description of the proposed feature
- Any relevant examples or mockups
- Explanation of why this feature would be useful

## Questions?

If you have any questions or need help, please open an issue or contact the project maintainers.

Thank you for contributing to the Loan Inventory System! 