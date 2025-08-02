# Flutter Mobile App API Documentation

## Complete E-commerce & Store Management APIs

### Base URL

```
{BASE_URL}/api
```

### Authentication

Most endpoints require authentication using Laravel Sanctum tokens.

**Header:**

```
Authorization: Bearer {token}
```

---

## 🔐 Authentication APIs

### 1. User Registration

**Endpoint:** `POST /register`

**Request Body:**

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "contact_number": "+1234567890",
  "user_type": "customer",
  "username": "johndoe"
}
```

**Response:**

```json
{
  "status": true,
  "message": "User registered successfully",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "user_type": "customer",
    "api_token": "1|abc123..."
  }
}
```

### 2. User Login

**Endpoint:** `POST /login`

**Request Body:**

```json
{
  "email": "john@example.com",
  "password": "password123",
  "user_type": "customer"
}
```

**Response:**

```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "user_type": "customer",
    "api_token": "1|abc123..."
  }
}
```

### 3. Forgot Password

**Endpoint:** `POST /forgot-password`

**Request Body:**

```json
{
  "email": "john@example.com"
}
```

### 4. Logout

**Endpoint:** `GET /logout`
**Authentication:** Required

---

## 🏪 Store APIs (Customer View)

### 1. Get Store Information

**Endpoint:** `GET /stores`

**Description:** Get main store information for the unified store experience

**Response:**

```json
{
  "status": true,
  "data": {
    "id": 1,
    "name": "Main Store",
    "description": "Our main store description",
    "slug": "main-store",
    "phone": "+1234567890",
    "address": "123 Main Street",
    "latitude": "40.7128",
    "longitude": "-74.0060",
    "status": "active",
    "is_active": true,
    "business_hours": {
      "monday": "9:00-18:00",
      "tuesday": "9:00-18:00"
    },
    "delivery_radius": 10,
    "minimum_order_amount": 25.0,
    "delivery_fee": 5.0,
    "is_open": true,
    "distance": null,
    "country": {
      "id": 1,
      "name": "United States"
    },
    "state": {
      "id": 1,
      "name": "New York"
    },
    "city": {
      "id": 1,
      "name": "New York City"
    }
  },
  "message": "Store details fetched successfully"
}
```

### 2. Get Store by ID

**Endpoint:** `GET /stores/{id}`

**Description:** Get specific store details

**Parameters:**

- `id` (required): Store ID

**Response:** Same as above

### 3. Get All Products

**Endpoint:** `GET /products`

**Description:** Get all available products from the unified store

**Query Parameters:**

- `per_page` (optional): Number of items per page (default: 15)
- `category_id` (optional): Filter by category ID
- `featured` (optional): Filter featured products (true/false)
- `search` (optional): Search term
- `latitude` (optional): User latitude for location-based filtering
- `longitude` (optional): User longitude for location-based filtering

**Response:**

```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "description": "Product description",
      "sku": "PROD001",
      "price": 29.99,
      "sale_price": 24.99,
      "weight": "1.5kg",
      "dimensions": "10x10x5cm",
      "track_inventory": true,
      "stock_quantity": 100,
      "low_stock_threshold": 10,
      "is_featured": true,
      "status": true,
      "is_in_stock": true,
      "is_low_stock": false,
      "sort_order": 1,
      "created_by_type": "admin",
      "store_price": null,
      "store_stock": null,
      "category": {
        "id": 1,
        "name": "Electronics",
        "description": "Electronic products"
      }
    }
  ],
  "message": "Products fetched successfully"
}
```

### 4. Get Product by ID

**Endpoint:** `GET /products/{id}`

**Description:** Get specific product details

**Parameters:**

- `id` (required): Product ID

**Query Parameters:**

- `store_id` (optional): Store ID
- `latitude` (optional): User latitude
- `longitude` (optional): User longitude

**Response:**

```json
{
  "status": true,
  "data": {
    "id": 1,
    "name": "Product Name",
    "description": "Detailed product description",
    "sku": "PROD001",
    "price": 29.99,
    "sale_price": 24.99,
    "weight": "1.5kg",
    "dimensions": "10x10x5cm",
    "track_inventory": true,
    "stock_quantity": 100,
    "low_stock_threshold": 10,
    "is_featured": true,
    "status": true,
    "is_in_stock": true,
    "is_low_stock": false,
    "category": {
      "id": 1,
      "name": "Electronics",
      "description": "Electronic products"
    },
    "variants": []
  },
  "available_stores": [],
  "message": "Product details fetched successfully"
}
```

### 5. Search Products

**Endpoint:** `GET /products-search`

**Description:** Search products by name, description, SKU, or category

**Query Parameters:**

- `query` (required): Search term
- `category_id` (optional): Filter by category ID
- `latitude` (optional): User latitude
- `longitude` (optional): User longitude
- `per_page` (optional): Number of items per page (default: 15)

**Response:** Same structure as Get All Products

### 6. Get Featured Products

**Endpoint:** `GET /featured-products`

**Description:** Get featured products only

**Query Parameters:**

- `per_page` (optional): Number of items per page (default: 15)

**Response:** Same structure as Get All Products

### 7. Get Product Categories

**Endpoint:** `GET /product-categories`

**Description:** Get all product categories

**Response:**

```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "description": "Electronic products",
      "slug": "electronics",
      "is_active": true,
      "sort_order": 1
    }
  ],
  "message": "Categories fetched successfully"
}
```

### 8. Get Store Products

**Endpoint:** `GET /stores/{id}/products`

**Description:** Get products for a specific store

**Parameters:**

- `id` (required): Store ID

**Query Parameters:**

- `per_page` (optional): Number of items per page (default: 15)
- `category_id` (optional): Filter by category ID
- `search` (optional): Search term

**Response:** Same structure as Get All Products

### 9. Get Nearby Stores

**Endpoint:** `GET /nearby-stores`

**Description:** Get stores near user location

**Query Parameters:**

- `latitude` (required): User latitude
- `longitude` (required): User longitude
- `radius` (optional): Search radius in km (default: 10)

**Response:** Same structure as Get Store Information (array format)

---

## Color Theme APIs

### Base URL

```
{BASE_URL}/api/v1/theme
```

### 1. Get All Theme Colors

**Endpoint:** `GET /colors`

**Description:** Get complete theme color configuration for mobile app

**Headers:**

```
Content-Type: application/json
Accept: application/json
```

**Response:**

```json
{
  "success": true,
  "data": {
    "brand_colors": {
      "yellow": {
        "light": "#F0B521",
        "dark": "#8D6710"
      },
      "red": {
        "light": "#EF5535",
        "dark": "#9B1F0B"
      },
      "green": {
        "light": "#2DB665",
        "dark": "#005F2D"
      },
      "blue": {
        "light": "#4A75FB",
        "dark": "#004CB2"
      }
    },
    "role_colors": {
      "admin": {
        "light": "#5F60B9",
        "dark": "#4153b3"
      },
      "provider": {
        "light": "#EF5535",
        "dark": "#9B1F0B"
      },
      "handyman": {
        "light": "#2DB665",
        "dark": "#005F2D"
      },
      "customer": {
        "light": "#4A75FB",
        "dark": "#004CB2"
      }
    }
  },
  "version": "1.0.0",
  "last_updated": "2024-01-15T10:30:00Z"
}
```

### 2. Get Role-Specific Theme

**Endpoint:** `GET /colors/{role}`

**Description:** Get theme colors for a specific user role

**Parameters:**

- `role` (required): User role (admin, provider, handyman, customer)

**Response:**

```json
{
  "success": true,
  "data": {
    "primary": {
      "light": "#4A75FB",
      "dark": "#004CB2"
    },
    "secondary": {
      "light": "#F0B521",
      "dark": "#8D6710"
    },
    "accent": {
      "light": "#2DB665",
      "dark": "#005F2D"
    },
    "background": {
      "light": "#FFFFFF",
      "dark": "#1A1A1A"
    },
    "surface": {
      "light": "#F5F5F5",
      "dark": "#2D2D2D"
    },
    "text": {
      "primary_light": "#000000",
      "primary_dark": "#FFFFFF",
      "secondary_light": "#666666",
      "secondary_dark": "#CCCCCC"
    }
  },
  "role": "customer",
  "version": "1.0.0"
}
```

### 3. Check Theme Update

**Endpoint:** `POST /check-update`

**Description:** Check if theme colors have been updated since last fetch

**Request Body:**

```json
{
  "current_version": "1.0.0",
  "last_updated": "2024-01-15T10:30:00Z"
}
```

**Response:**

```json
{
  "success": true,
  "update_available": false,
  "current_version": "1.0.0",
  "latest_version": "1.0.0",
  "message": "Theme is up to date"
}
```

**Response (Update Available):**

```json
{
  "success": true,
  "update_available": true,
  "current_version": "1.0.0",
  "latest_version": "1.1.0",
  "message": "Theme update available",
  "changes": ["Updated primary colors", "Added new accent colors"]
}
```

---

## Error Responses

### Standard Error Format

```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message",
  "status_code": 400
}
```

### Common HTTP Status Codes

- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

---

## Authentication

Most endpoints require authentication using Laravel Sanctum tokens.

**Header:**

```
Authorization: Bearer {token}
```

**Getting Token:**
Use the login endpoint to get authentication token:

```
POST /login
```

---

## Rate Limiting

API requests are rate limited to prevent abuse:

- **Public endpoints:** 60 requests per minute
- **Authenticated endpoints:** 120 requests per minute

---

## Caching

Theme colors are cached for 1 hour to improve performance. Use the check-update endpoint to verify if cached data is still valid.
