# E-commerce API Documentation

## Overview
This document provides comprehensive API endpoints for the e-commerce system, organized by user roles for mobile app integration.

## Authentication
All API requests require authentication using Bearer tokens:
```
Authorization: Bearer {token}
```

## Base URL
```
{base_url}/api/
```

---

## 🔐 ADMIN ENDPOINTS

### Product Categories Management

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/admin/product-categories` | List all categories (including soft-deleted) | `page`, `per_page`, `search` | Categories list with pagination |
| POST | `/admin/product-categories` | Create new category | `name`, `slug`, `description`, `is_featured`, `status` | Created category object |
| GET | `/admin/product-categories/{id}` | Get category details | `id` | Category object |
| PUT | `/admin/product-categories/{id}` | Update category | `name`, `slug`, `description`, `is_featured`, `status` | Updated category object |
| DELETE | `/admin/product-categories/{id}` | Soft delete category | `id` | Success message |
| POST | `/admin/product-categories/{id}/restore` | Restore soft-deleted category | `id` | Restored category object |

### Products Management

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/admin/products` | List all products (including soft-deleted) | `page`, `per_page`, `search`, `category_id`, `status` | Products list with pagination |
| POST | `/admin/products` | Create new product | `name`, `slug`, `sku`, `description`, `base_price`, `product_category_id`, `stock_quantity`, `is_featured`, `status` | Created product object |
| GET | `/admin/products/{id}` | Get product details | `id` | Product object with category |
| PUT | `/admin/products/{id}` | Update product | `name`, `slug`, `sku`, `description`, `base_price`, `product_category_id`, `stock_quantity`, `is_featured`, `status` | Updated product object |
| DELETE | `/admin/products/{id}` | Soft delete product | `id` | Success message |
| POST | `/admin/products/{id}/restore` | Restore soft-deleted product | `id` | Restored product object |

### Stores Management

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/admin/stores` | List all stores (including soft-deleted) | `page`, `per_page`, `search`, `status`, `is_approved` | Stores list with pagination |
| POST | `/admin/stores` | Create store for provider | `name`, `description`, `provider_id`, `address`, `contact_number`, `email`, `status` | Created store object |
| GET | `/admin/stores/{id}` | Get store details | `id` | Store object with provider |
| PUT | `/admin/stores/{id}` | Update store | `name`, `description`, `address`, `contact_number`, `email`, `status`, `is_approved` | Updated store object |
| DELETE | `/admin/stores/{id}` | Soft delete store | `id` | Success message |
| POST | `/admin/stores/{id}/restore` | Restore soft-deleted store | `id` | Restored store object |
| POST | `/admin/stores/{id}/approve` | Approve store | `id` | Approved store object |
| POST | `/admin/stores/{id}/reject` | Reject store | `id` | Rejected store object |

### Orders Management

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/admin/orders` | List all orders | `page`, `per_page`, `search`, `status`, `payment_status` | Orders list with pagination |
| GET | `/admin/orders/{id}` | Get order details | `id` | Order object with items and customer |
| PUT | `/admin/orders/{id}` | Update order | `status`, `payment_status`, `notes` | Updated order object |
| PUT | `/admin/orders/{id}/status` | Update order status | `status` | Updated order object |

### Dynamic Pricing Management

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/admin/dynamic-pricing` | List all pricing rules | `page`, `per_page`, `product_id` | Pricing rules list |
| POST | `/admin/dynamic-pricing` | Create pricing rule | `product_id`, `price_override`, `start_date`, `end_date`, `is_active` | Created pricing rule |
| PUT | `/admin/dynamic-pricing/{id}` | Update pricing rule | `price_override`, `start_date`, `end_date`, `is_active` | Updated pricing rule |
| DELETE | `/admin/dynamic-pricing/{id}` | Delete pricing rule | `id` | Success message |

---

## 🏪 PROVIDER ENDPOINTS

### Product Categories (Read Only)

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/provider/product-categories` | List active categories | `page`, `per_page`, `search` | Active categories list |
| GET | `/provider/product-categories/{id}` | Get category details | `id` | Category object |

### Products Management (Own Products Only)

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/provider/products` | List own products | `page`, `per_page`, `search`, `category_id`, `status` | Own products list |
| POST | `/provider/products` | Create new product | `name`, `slug`, `sku`, `description`, `base_price`, `product_category_id`, `stock_quantity`, `is_featured`, `status` | Created product object |
| GET | `/provider/products/{id}` | Get own product details | `id` | Product object |
| PUT | `/provider/products/{id}` | Update own product | `name`, `slug`, `sku`, `description`, `base_price`, `product_category_id`, `stock_quantity`, `is_featured`, `status` | Updated product object |
| DELETE | `/provider/products/{id}` | Soft delete own product | `id` | Success message |

### Stores Management (Own Store Only)

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/provider/stores` | List own stores | `page`, `per_page` | Own stores list |
| POST | `/provider/stores` | Create new store | `name`, `description`, `address`, `contact_number`, `email` | Created store object |
| GET | `/provider/stores/{id}` | Get own store details | `id` | Store object |
| PUT | `/provider/stores/{id}` | Update own store | `name`, `description`, `address`, `contact_number`, `email` | Updated store object |
| DELETE | `/provider/stores/{id}` | Soft delete own store | `id` | Success message |

### Orders Management (Own Orders Only)

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/provider/orders` | List orders for own products | `page`, `per_page`, `status` | Orders list |
| GET | `/provider/orders/{id}` | Get order details | `id` | Order object with items |
| PUT | `/provider/orders/{id}/status` | Update order status | `status` | Updated order object |

---

## 👤 USER ENDPOINTS

### Product Categories (Browse)

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/user/product-categories` | List active categories | `page`, `per_page`, `search`, `is_featured` | Active categories list |
| GET | `/user/product-categories/{id}` | Get category details | `id` | Category object |
| GET | `/user/product-categories/{id}/products` | Get products in category | `id`, `page`, `per_page`, `search` | Products in category |

### Products (Browse)

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/user/products` | List active products | `page`, `per_page`, `search`, `category_id`, `is_featured`, `min_price`, `max_price` | Active products list |
| GET | `/user/products/{id}` | Get product details | `id` | Product object with category |
| GET | `/user/products/featured` | Get featured products | `page`, `per_page` | Featured products list |
| GET | `/user/products/search` | Search products | `q`, `page`, `per_page`, `category_id` | Search results |

### Stores (Browse)

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/user/stores` | List approved stores | `page`, `per_page`, `search` | Approved stores list |
| GET | `/user/stores/{id}` | Get store details | `id` | Store object |
| GET | `/user/stores/{id}/products` | Get products in store | `id`, `page`, `per_page`, `search` | Store products list |

### Orders Management

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/user/orders` | List own orders | `page`, `per_page`, `status` | Own orders list |
| POST | `/user/orders` | Create new order | `items[]`, `delivery_address`, `notes` | Created order object |
| GET | `/user/orders/{id}` | Get own order details | `id` | Order object with items |
| PUT | `/user/orders/{id}/cancel` | Cancel order | `id` | Cancelled order object |

### Cart Management

| Method | Endpoint | Description | Parameters | Response |
|--------|----------|-------------|------------|----------|
| GET | `/user/cart` | Get cart items | - | Cart items list |
| POST | `/user/cart` | Add item to cart | `product_id`, `quantity` | Updated cart |
| PUT | `/user/cart/{item_id}` | Update cart item | `quantity` | Updated cart item |
| DELETE | `/user/cart/{item_id}` | Remove item from cart | `item_id` | Success message |
| DELETE | `/user/cart` | Clear cart | - | Success message |

---

## 📊 COMMON RESPONSE FORMATS

### Success Response
```json
{
    "success": true,
    "data": {...},
    "message": "Operation successful"
}
```

### Error Response
```json
{
    "success": false,
    "error": "Error message",
    "errors": {...}
}
```

### Pagination Response
```json
{
    "success": true,
    "data": [...],
    "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7,
        "from": 1,
        "to": 15
    }
}
```

---

## 🔒 PERMISSION REQUIREMENTS

### Admin Permissions
- Full access to all endpoints
- Can manage all data including soft-deleted items
- Can approve/reject stores
- Can override pricing

### Provider Permissions
- Limited to own resources (products, stores, orders)
- Cannot access other providers' data
- Store creation requires admin approval
- Cannot see soft-deleted items

### User Permissions
- Read-only access to public data
- Can manage own orders and cart
- Cannot access admin or provider endpoints
- Can only see active/approved items

---

## 📱 MOBILE APP INTEGRATION NOTES

1. **Authentication**: Use JWT tokens for session management
2. **Pagination**: All list endpoints support pagination
3. **Search**: Most endpoints support search functionality
4. **Filtering**: Products and orders support various filters
5. **Real-time Updates**: Consider WebSocket for order status updates
6. **Image Handling**: Product images should be handled via separate upload endpoints
7. **Caching**: Implement caching for frequently accessed data like categories
8. **Error Handling**: Always check the `success` field in responses

---

## 🚀 DEPLOYMENT READY

This API documentation covers all endpoints needed for a complete e-commerce mobile application with proper role-based access control and comprehensive functionality for admins, providers, and users.
