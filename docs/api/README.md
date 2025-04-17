# API Documentation

This section contains documentation for the Loan Inventory System API, which allows third-party applications to interact with the system programmatically.

## Overview

The Loan Inventory System provides a RESTful API for interacting with inventory items, loans, categories, departments, and users. The API uses JSON for request and response payloads and requires authentication via API tokens.

## Authentication

All API requests require authentication. The API uses Laravel Sanctum for token-based authentication.

To authenticate, include an `Authorization` header with a Bearer token:

```
Authorization: Bearer YOUR_API_TOKEN
```

API tokens can be generated in the user profile section of the admin panel.

## Endpoints

- [Items API](./endpoints/items.md)
- [Loans API](./endpoints/loans.md)
- [Categories API](./endpoints/categories.md)
- [Departments API](./endpoints/departments.md)
- [Users API](./endpoints/users.md)

## Response Format

API responses follow a consistent format:

### Success Response

```json
{
  "data": {
    // Response data
  },
  "message": "Optional success message",
  "meta": {
    // Pagination or other metadata
  }
}
```

### Error Response

```json
{
  "message": "Error message",
  "errors": {
    // Validation errors or other details
  }
}
```

## Status Codes

The API uses standard HTTP status codes:

- `200 OK`: The request was successful
- `201 Created`: A resource was successfully created
- `400 Bad Request`: The request was invalid
- `401 Unauthorized`: Authentication failed
- `403 Forbidden`: The authenticated user doesn't have permission
- `404 Not Found`: The requested resource was not found
- `422 Unprocessable Entity`: Validation failed
- `500 Internal Server Error`: Something went wrong on the server

## Rate Limiting

API requests are rate-limited to 60 requests per minute per API token. When this limit is exceeded, the API will return a `429 Too Many Requests` status code.

## Pagination

List endpoints support pagination using `page` and `per_page` query parameters:

```
GET /api/items?page=2&per_page=15
```

## Filtering and Sorting

Most list endpoints support filtering and sorting using query parameters:

```
GET /api/items?sort=-created_at&filter[category_id]=3
```

## Example Usage

Here's an example of how to use the API with cURL:

```bash
curl -X GET \
  https://your-domain.com/api/items \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer YOUR_API_TOKEN'
```

And with JavaScript/Fetch:

```javascript
fetch('https://your-domain.com/api/items', {
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer ${apiToken}`
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

## Support

For API support, please contact the system administrator. 