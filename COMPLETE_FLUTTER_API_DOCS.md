# Complete Flutter Mobile App API Documentation

## E-commerce & Store Management APIs for All User Roles

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

### 4. Update Profile

**Endpoint:** `POST /update-profile`
**Authentication:** Required

**Request Body:**

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "contact_number": "+1234567890",
  "address": "123 Main Street"
}
```

### 5. Change Password

**Endpoint:** `POST /change-password`
**Authentication:** Required

**Request Body:**

```json
{
  "old_password": "oldpass123",
  "password": "newpass123",
  "password_confirmation": "newpass123"
}
```

### 6. Logout

**Endpoint:** `GET /logout`
**Authentication:** Required

---

## 🏪 Store & Product APIs (Public)

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
    "phone": "+1234567890",
    "address": "123 Main Street",
    "business_hours": {
      "monday": "9:00-18:00",
      "tuesday": "9:00-18:00"
    },
    "delivery_radius": 10,
    "minimum_order_amount": 25.0,
    "delivery_fee": 5.0,
    "is_open": true
  },
  "message": "Store details fetched successfully"
}
```

### 2. Get All Products

**Endpoint:** `GET /products`

**Query Parameters:**

- `per_page` (optional): Number of items per page (default: 15)
- `category_id` (optional): Filter by category ID
- `featured` (optional): Filter featured products (true/false)
- `search` (optional): Search term

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
      "stock_quantity": 100,
      "is_featured": true,
      "is_in_stock": true,
      "category": {
        "id": 1,
        "name": "Electronics"
      }
    }
  ],
  "message": "Products fetched successfully"
}
```

### 3. Get Product Details

**Endpoint:** `GET /products/{id}`

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
    "stock_quantity": 100,
    "is_featured": true,
    "is_in_stock": true,
    "category": {
      "id": 1,
      "name": "Electronics"
    },
    "variants": [],
    "images": []
  },
  "message": "Product details fetched successfully"
}
```

### 4. Search Products

**Endpoint:** `GET /products-search`

**Query Parameters:**

- `query` (required): Search term
- `category_id` (optional): Filter by category ID
- `per_page` (optional): Number of items per page (default: 15)

**Response:** Same structure as Get All Products

### 5. Get Featured Products

**Endpoint:** `GET /featured-products`

**Query Parameters:**

- `per_page` (optional): Number of items per page (default: 15)

**Response:** Same structure as Get All Products

### 6. Get Product Categories

**Endpoint:** `GET /product-categories`

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
      "is_active": true
    }
  ],
  "message": "Categories fetched successfully"
}
```

---

## 🛒 Customer Shopping APIs

### 1. Create Direct Order

**Endpoint:** `POST /orders`
**Authentication:** Required

**Description:** Create order directly (simplified mobile flow)

**Request Body:**

```json
{
  "product_id": 1,
  "quantity": 2,
  "payment_method": "stripe",
  "shipping_address": {
    "name": "John Doe",
    "phone": "+1234567890",
    "address": "123 Main St",
    "city": "New York",
    "state": "NY",
    "postal_code": "10001",
    "country": "US"
  },
  "notes": "Please deliver after 5 PM"
}
```

**Response:**

```json
{
  "status": true,
  "data": {
    "order_id": 123,
    "order_number": "ORD-2024-001",
    "total_amount": 59.98,
    "payment_method": "stripe"
  },
  "message": "Order created successfully"
}
```

### 2. Get Customer Orders

**Endpoint:** `GET /orders`
**Authentication:** Required

**Query Parameters:**

- `per_page` (optional): Number of items per page (default: 15)
- `status` (optional): Filter by order status

**Response:**

```json
{
  "status": true,
  "data": [
    {
      "id": 123,
      "order_number": "ORD-2024-001",
      "status": "processing",
      "payment_status": "paid",
      "total_amount": 59.98,
      "created_at": "2024-01-15T10:30:00Z",
      "items": [
        {
          "id": 1,
          "product_name": "Product Name",
          "quantity": 2,
          "unit_price": 29.99,
          "total_price": 59.98
        }
      ]
    }
  ],
  "message": "Orders fetched successfully"
}
```

### 3. Get Order Details

**Endpoint:** `GET /orders/{id}`
**Authentication:** Required

**Response:**

```json
{
  "status": true,
  "data": {
    "id": 123,
    "order_number": "ORD-2024-001",
    "status": "processing",
    "payment_status": "paid",
    "total_amount": 59.98,
    "shipping_address": {
      "name": "John Doe",
      "phone": "+1234567890",
      "address": "123 Main St",
      "city": "New York"
    },
    "items": [
      {
        "id": 1,
        "product_name": "Product Name",
        "quantity": 2,
        "unit_price": 29.99,
        "total_price": 59.98
      }
    ],
    "status_history": [
      {
        "status": "pending",
        "changed_at": "2024-01-15T10:30:00Z",
        "changed_by": "System"
      }
    ]
  },
  "message": "Order details fetched successfully"
}
```

### 4. Cancel Order

**Endpoint:** `POST /orders/{id}/cancel`
**Authentication:** Required

**Request Body:**

```json
{
  "reason": "Changed my mind"
}
```

### 5. Track Order

**Endpoint:** `GET /orders/{id}/track`
**Authentication:** Required

**Response:**

```json
{
  "status": true,
  "data": {
    "order_id": 123,
    "current_status": "shipped",
    "tracking_number": "TRK123456",
    "estimated_delivery": "2024-01-20",
    "status_timeline": [
      {
        "status": "pending",
        "timestamp": "2024-01-15T10:30:00Z",
        "description": "Order placed"
      },
      {
        "status": "processing",
        "timestamp": "2024-01-15T14:00:00Z",
        "description": "Order being prepared"
      },
      {
        "status": "shipped",
        "timestamp": "2024-01-16T09:00:00Z",
        "description": "Order shipped"
      }
    ]
  },
  "message": "Order tracking information"
}
```

---

## 💳 Payment APIs

### 1. Get Payment Methods

**Endpoint:** `POST /get-payment-method`
**Authentication:** Required

**Response:**

```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "type": "stripe",
      "title": "Credit Card",
      "status": 1
    },
    {
      "id": 2,
      "type": "wallet",
      "title": "Wallet",
      "status": 1
    }
  ],
  "message": "Payment methods fetched successfully"
}
```

### 2. Create Stripe Payment

**Endpoint:** `POST /create-stripe-payment`
**Authentication:** Required

**Request Body:**

```json
{
  "order_id": 123,
  "amount": 59.98,
  "currency": "USD"
}
```

### 3. Process Wallet Payment

**Endpoint:** `POST /process-product-wallet-payment`
**Authentication:** Required

**Request Body:**

```json
{
  "order_id": 123,
  "amount": 59.98
}
```

---

## 🏭 Provider APIs

### 1. Provider Dashboard

**Endpoint:** `GET /provider-dashboard`
**Authentication:** Required (Provider role)

**Response:**

```json
{
  "status": true,
  "data": {
    "total_products": 25,
    "pending_products": 3,
    "approved_products": 20,
    "rejected_products": 2,
    "total_orders": 150,
    "pending_orders": 5,
    "completed_orders": 140,
    "total_earnings": 5000.0,
    "recent_orders": []
  },
  "message": "Dashboard data fetched successfully"
}
```

### 2. Create Product (Provider)

**Endpoint:** `POST /products`
**Authentication:** Required (Provider role)

**Request Body:**

```json
{
  "name": "New Product",
  "description": "Product description",
  "short_description": "Short description",
  "product_category_id": 1,
  "base_price": 29.99,
  "sale_price": 24.99,
  "weight": 1.5,
  "stock_quantity": 100,
  "low_stock_threshold": 10,
  "sku": "PROD001",
  "is_featured": true
}
```

**Response:**

```json
{
  "status": true,
  "data": {
    "id": 1,
    "name": "New Product",
    "approval_status": "pending",
    "status": false,
    "is_available": false
  },
  "message": "Product created successfully and sent for approval"
}
```

### 3. Get Provider Products

**Endpoint:** `GET /provider/products`
**Authentication:** Required (Provider role)

**Query Parameters:**

- `per_page` (optional): Number of items per page (default: 15)
- `status` (optional): Filter by status
- `approval_status` (optional): Filter by approval status

**Response:**

```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "approval_status": "approved",
      "status": true,
      "stock_quantity": 100,
      "base_price": 29.99,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "message": "Products fetched successfully"
}
```

### 4. Update Product (Provider)

**Endpoint:** `PUT /provider/products/{id}`
**Authentication:** Required (Provider role)

**Request Body:** Same as Create Product

### 5. Delete Product (Provider)

**Endpoint:** `DELETE /provider/products/{id}`
**Authentication:** Required (Provider role)

### 6. Get Provider Orders

**Endpoint:** `GET /provider/orders`
**Authentication:** Required (Provider role)

**Description:** Get orders containing products created by this provider

**Query Parameters:**

- `per_page` (optional): Number of items per page (default: 15)
- `status` (optional): Filter by order status
- `payment_status` (optional): Filter by payment status

**Response:**

```json
{
  "status": true,
  "data": [
    {
      "id": 123,
      "order_number": "ORD-2024-001",
      "status": "processing",
      "payment_status": "paid",
      "total_amount": 59.98,
      "customer_name": "John Doe",
      "created_at": "2024-01-15T10:30:00Z",
      "provider_items": [
        {
          "product_name": "My Product",
          "quantity": 2,
          "unit_price": 29.99
        }
      ]
    }
  ],
  "message": "Orders fetched successfully"
}
```

### 7. Update Order Status (Provider)

**Endpoint:** `POST /provider/order-update-status`
**Authentication:** Required (Provider role)

**Request Body:**

```json
{
  "order_id": 123,
  "status": "shipped",
  "notes": "Order shipped via FedEx"
}
```

---

## 👨‍💼 Admin APIs

### 1. Admin Dashboard

**Endpoint:** `GET /admin-dashboard`
**Authentication:** Required (Admin role)

**Response:**

```json
{
  "status": true,
  "data": {
    "total_products": 500,
    "pending_approval": 15,
    "total_orders": 1000,
    "total_revenue": 50000.0,
    "total_customers": 200,
    "total_providers": 50,
    "recent_orders": [],
    "pending_products": []
  },
  "message": "Admin dashboard data"
}
```

### 2. Get All Products (Admin)

**Endpoint:** `GET /admin/products`
**Authentication:** Required (Admin role)

**Query Parameters:**

- `per_page` (optional): Number of items per page (default: 15)
- `approval_status` (optional): Filter by approval status
- `created_by_type` (optional): Filter by creator type (admin/provider)

### 3. Approve Product (Admin)

**Endpoint:** `POST /admin/products/{id}/approve`
**Authentication:** Required (Admin role)

**Response:**

```json
{
  "status": true,
  "message": "Product approved successfully and is now available in the store"
}
```

### 4. Reject Product (Admin)

**Endpoint:** `POST /admin/products/{id}/reject`
**Authentication:** Required (Admin role)

**Request Body:**

```json
{
  "rejection_reason": "Product does not meet quality standards"
}
```

### 5. Bulk Product Actions (Admin)

**Endpoint:** `POST /admin/ecommerce/products/bulk-action`
**Authentication:** Required (Admin role)

**Request Body:**

```json
{
  "action": "bulk-approve",
  "product_ids": [1, 2, 3, 4, 5]
}
```

**Available Actions:**

- `bulk-approve`: Approve multiple products
- `bulk-reject`: Reject multiple products
- `reconsider`: Move rejected products back to pending

### 6. Get All Orders (Admin)

**Endpoint:** `GET /admin/orders`
**Authentication:** Required (Admin role)

**Query Parameters:**

- `per_page` (optional): Number of items per page (default: 15)
- `status` (optional): Filter by order status
- `payment_status` (optional): Filter by payment status

### 7. Update Order Status (Admin)

**Endpoint:** `PUT /admin/orders/{id}/status`
**Authentication:** Required (Admin role)

**Request Body:**

```json
{
  "status": "delivered",
  "notes": "Order delivered successfully"
}
```

### 8. Update Payment Status (Admin)

**Endpoint:** `PUT /admin/orders/{id}/payment-status`
**Authentication:** Required (Admin role)

**Request Body:**

```json
{
  "payment_status": "paid"
}
```

### 9. Store Management (Admin)

**Endpoint:** `POST /admin/stores`
**Authentication:** Required (Admin role)

**Description:** Create or update main store configuration

**Request Body:**

```json
{
  "name": "Main Store",
  "description": "Our main store",
  "address": "123 Main Street",
  "phone": "+1234567890",
  "business_hours": {
    "monday": "9:00-18:00",
    "tuesday": "9:00-18:00"
  },
  "delivery_radius": 10,
  "minimum_order_amount": 25.0,
  "delivery_fee": 5.0
}
```

---

## 🎨 Color Theme APIs

### Base URL

```
{BASE_URL}/api/v1/theme
```

### 1. Get All Theme Colors

**Endpoint:** `GET /colors`

**Description:** Get complete theme color configuration for mobile app

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

## 📊 Common Response Formats

### Success Response

```json
{
  "status": true,
  "data": {...},
  "message": "Operation successful"
}
```

### Error Response

```json
{
  "status": false,
  "message": "Error description",
  "errors": {...}
}
```

### Pagination Response

```json
{
  "status": true,
  "data": [...],
  "pagination": {
    "total_items": 100,
    "per_page": 15,
    "current_page": 1,
    "total_pages": 7,
    "from": 1,
    "to": 15,
    "next_page": "url",
    "previous_page": null
  },
  "message": "Data fetched successfully"
}
```

---

## 🔒 Order Status Values

### Order Statuses

- `pending`: Order placed, awaiting processing
- `processing`: Order being prepared
- `shipped`: Order shipped to customer
- `delivered`: Order delivered successfully
- `cancelled`: Order cancelled
- `refunded`: Order refunded

### Payment Statuses

- `pending`: Payment not yet processed
- `paid`: Payment completed successfully
- `failed`: Payment failed
- `refunded`: Payment refunded

### Product Approval Statuses

- `pending`: Awaiting admin approval
- `approved`: Approved and available in store
- `rejected`: Rejected by admin

---

## 🚫 Error Codes

### HTTP Status Codes

- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

### Custom Error Messages

- `messages.user_not_found` - User not found
- `messages.product_not_found` - Product not found
- `messages.order_not_found` - Order not found
- `messages.insufficient_stock` - Insufficient stock
- `messages.payment_failed` - Payment processing failed
- `messages.unauthorized_access` - Unauthorized access

---

## 🔄 Rate Limiting

API requests are rate limited to prevent abuse:

- **Public endpoints:** 60 requests per minute
- **Authenticated endpoints:** 120 requests per minute

---

## 💾 Caching

- **Theme colors:** Cached for 1 hour
- **Product data:** Cached for 30 minutes
- **Store information:** Cached for 2 hours

Use appropriate cache headers and version checking endpoints to ensure data freshness.

---

## 🔧 Development Notes

### Testing Endpoints

- Use `/test-store` endpoint to verify store APIs are working
- All endpoints support both JSON and form-data requests
- Use proper authentication headers for protected endpoints

### Mobile App Integration

- Implement proper error handling for all API calls
- Use pagination for list endpoints to improve performance
- Cache theme colors locally and check for updates periodically
- Implement offline support for critical functionality

### Security Considerations

- Always validate user permissions before API calls
- Use HTTPS for all API communications
- Implement proper token refresh mechanisms
- Validate all input data on both client and server side
