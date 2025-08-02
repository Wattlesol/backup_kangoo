# Missing API Endpoints for Complete Store Management

## 🏪 Store Management Endpoints (Admin)

### **Store Status & Configuration**
```php
// Route: POST /api/store-status-update
// Controller: StoreController@updateStatus
// Purpose: Update store active/inactive status
{
    "id": 1,
    "is_active": true,
    "status": "active"
}
```

```php
// Route: POST /api/store-logo-upload
// Controller: StoreController@uploadLogo
// Purpose: Upload store logo/branding
// Form data: store_logo (file), store_id (int)
```

```php
// Route: GET /api/store-analytics
// Controller: StoreController@analytics
// Purpose: Get store performance analytics
// Query params: period, metrics
```

```php
// Route: POST /api/store-business-hours
// Controller: StoreController@updateBusinessHours
// Purpose: Update store operating hours
{
    "store_id": 1,
    "business_hours": {
        "monday": {"open": "09:00", "close": "18:00", "is_open": true},
        "tuesday": {"open": "09:00", "close": "18:00", "is_open": true}
        // ... other days
    }
}
```

```php
// Route: POST /api/store-delivery-settings
// Controller: StoreController@updateDeliverySettings
// Purpose: Configure delivery options and pricing
{
    "store_id": 1,
    "delivery_radius": 25.5,
    "minimum_order_amount": 20.00,
    "delivery_fee": 4.99,
    "free_delivery_threshold": 75.00,
    "delivery_time_slots": ["09:00-12:00", "12:00-15:00"]
}
```

```php
// Route: POST /api/store-policies-update
// Controller: StoreController@updatePolicies
// Purpose: Update store policies and terms
{
    "store_id": 1,
    "terms_and_conditions": "Our terms...",
    "privacy_policy": "Our privacy...",
    "return_policy": "Our return...",
    "refund_policy": "Our refund..."
}
```

## 🛍️ Product Management Endpoints (Admin)

### **Product Analytics & Stock Management**
```php
// Route: GET /api/product-analytics
// Controller: ProductController@analytics
// Purpose: Get product performance metrics
// Query params: product_id, period
```

```php
// Route: POST /api/product-stock-update
// Controller: ProductController@updateStock
// Purpose: Update product inventory
{
    "product_id": 1,
    "stock_quantity": 150,
    "low_stock_threshold": 10,
    "track_inventory": true
}
```

```php
// Route: POST /api/product-images-upload
// Controller: ProductController@uploadImages
// Purpose: Upload product images
// Form data: product_images[] (files), product_id (int), is_primary (bool)
```

## ⚙️ Store Configuration Endpoints (Admin)

### **Advanced Settings Management**
```php
// Route: GET /api/store-settings
// Controller: StoreController@getSettings
// Purpose: Get all store configuration settings
```

```php
// Route: POST /api/store-settings-update
// Controller: StoreController@updateSettings
// Purpose: Update comprehensive store settings
{
    "store_settings": {
        "currency": "USD",
        "tax_rate": 8.5,
        "enable_reviews": true,
        "enable_wishlist": true,
        "auto_approve_orders": false,
        "commission_rate": 15.0
    },
    "payment_methods": ["stripe", "paypal", "wallet"],
    "shipping_methods": [
        {"name": "Standard", "cost": 5.99, "estimated_days": 3}
    ]
}
```

## 📊 Analytics Endpoints

### **Comprehensive Analytics**
```php
// Route: GET /api/admin-analytics
// Controller: AdminController@analytics
// Purpose: Get comprehensive admin dashboard analytics
// Query params: period, metrics, category_id
```

```php
// Route: GET /api/sales-analytics
// Controller: SalesController@analytics
// Purpose: Get detailed sales analytics
// Query params: period, product_id, category_id
```

## 🎨 Enhanced Theme Management

### **Advanced Theme Features**
```php
// Route: POST /api/theme-export
// Controller: ThemeController@exportTheme
// Purpose: Export current theme configuration
```

```php
// Route: POST /api/theme-import
// Controller: ThemeController@importTheme
// Purpose: Import theme configuration
// Form data: theme_file (json)
```

## 💰 Enhanced Dynamic Pricing

### **Advanced Pricing Features**
```php
// Route: POST /api/pricing-rules-create
// Controller: DynamicPricingController@createRule
// Purpose: Create automated pricing rules
{
    "rule_name": "Competitive Pricing",
    "conditions": {
        "category_id": 1,
        "min_price": 10.00,
        "max_price": 500.00
    },
    "action": {
        "type": "percentage_below_lowest",
        "value": 5
    }
}
```

```php
// Route: GET /api/pricing-history
// Controller: DynamicPricingController@priceHistory
// Purpose: Get price change history for products
// Query params: product_id, period
```

## 🔔 Notification System

### **Admin Notifications**
```php
// Route: GET /api/admin-notifications
// Controller: NotificationController@adminNotifications
// Purpose: Get admin notifications (new orders, low stock, etc.)
```

```php
// Route: POST /api/notification-settings
// Controller: NotificationController@updateSettings
// Purpose: Configure notification preferences
{
    "email_notifications": true,
    "sms_notifications": false,
    "notification_types": ["new_order", "low_stock", "product_approval"]
}
```

## 📈 Reporting System

### **Advanced Reports**
```php
// Route: GET /api/reports/sales
// Controller: ReportController@salesReport
// Purpose: Generate detailed sales reports
// Query params: start_date, end_date, format (pdf/excel/json)
```

```php
// Route: GET /api/reports/inventory
// Controller: ReportController@inventoryReport
// Purpose: Generate inventory reports
```

```php
// Route: GET /api/reports/customers
// Controller: ReportController@customerReport
// Purpose: Generate customer analytics reports
```

## 🚀 Implementation Priority

### **High Priority (Core Functionality)**
1. Store status management
2. Store analytics
3. Product stock management
4. Product analytics
5. Store settings management

### **Medium Priority (Enhanced Features)**
1. Business hours management
2. Delivery settings
3. Product image uploads
4. Advanced pricing rules
5. Notification system

### **Low Priority (Advanced Features)**
1. Theme import/export
2. Advanced reporting
3. Policy management
4. Price history tracking

## 📝 Implementation Notes

- All endpoints should include proper authentication middleware
- Admin-only endpoints should have admin role verification
- File uploads should include validation and security checks
- Analytics endpoints should support caching for performance
- All endpoints should return consistent JSON response format
- Include proper error handling and validation
- Add rate limiting for resource-intensive endpoints
