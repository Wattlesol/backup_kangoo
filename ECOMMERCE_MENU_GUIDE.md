# E-Commerce Menu Integration Guide

## Overview
The e-commerce system has been successfully integrated into the Kangoo admin panel sidebar with dedicated menu sections for both administrators and providers.

## Admin Menu Section

### Location
The e-commerce menu appears in the admin sidebar after the "Booking" section.

### Menu Items
1. **E-commerce** (Main Category Header)
   - **Product Categories** → `/productcategory`
     - Manage product categories
     - Create, edit, delete categories
     - Set featured categories
   
   - **Products** → `/product`
     - Manage all products (admin and provider products)
     - Create, edit, delete products
     - Bulk operations
   
   - **Stores** → `/store`
     - Manage provider stores
     - Approve/reject store applications
     - View store details and performance
   
   - **Orders** → `/order`
     - Manage all orders from all stores
     - Update order statuses
     - View order details and history
   
   - **Dynamic Pricing** → `/dynamic-pricing`
     - Advanced pricing management
     - Set pricing strategies (lowest, highest, fixed)
     - Bulk pricing operations
     - Price analytics

## Provider Menu Section

### Location
The provider e-commerce menu appears within the provider-specific section of the sidebar (only visible to providers).

### Menu Items
1. **My Store** → `/provider/store`
   - Create and manage provider's store
   - Update store information
   - View store status (pending/approved/rejected)

2. **My Products** → `/provider/product`
   - Manage provider's own products
   - Add products to store inventory
   - Update product pricing and stock

3. **My Orders** → `/provider/orders`
   - View orders for provider's store
   - Update order statuses
   - Process deliveries

## Permissions

### Admin Permissions
- `product_category list` - Access to product categories
- `product list` - Access to products and dynamic pricing
- `store list` - Access to store management
- `order list` - Access to order management

### Provider Permissions
- Providers automatically have access to their own store, products, and orders
- No additional permissions required for provider e-commerce features

## Language Support

All menu items support internationalization through the language files:
- `messages.ecommerce` - E-commerce
- `messages.product_categories` - Product Categories
- `messages.products` - Products
- `messages.stores` - Stores
- `messages.orders` - Orders
- `messages.dynamic_pricing` - Dynamic Pricing
- `messages.my_store` - My Store
- `messages.my_products` - My Products
- `messages.my_orders` - My Orders

## Icons

Each menu item includes appropriate SVG icons:
- **Product Categories**: Shopping cart icon
- **Products**: Package/box icon
- **Stores**: Store/building icon
- **Orders**: Document/list icon
- **Dynamic Pricing**: Dollar sign icon

## Navigation Flow

### For Administrators:
1. Login to admin panel
2. Navigate to "E-commerce" section in sidebar
3. Access any e-commerce feature
4. Manage products, stores, orders, and pricing

### For Providers:
1. Login to provider panel
2. Navigate to provider-specific e-commerce items
3. Manage store, products, and orders
4. Process customer orders

## Quick Access Routes

### Admin Routes:
- Product Categories: `/productcategory`
- Products: `/product`
- Stores: `/store`
- Orders: `/order`
- Dynamic Pricing: `/dynamic-pricing`

### Provider Routes:
- My Store: `/provider/store`
- My Products: `/provider/product`
- My Orders: `/provider/orders`

### Frontend Routes:
- Product Listing: `/products`
- Store Listing: `/stores`
- Shopping Cart: `/cart`
- Checkout: `/checkout`

## Features Accessible Through Menu

### Admin Features:
- Complete product catalog management
- Store approval workflow
- Order oversight and management
- Advanced dynamic pricing controls
- Analytics and reporting
- Bulk operations

### Provider Features:
- Store creation and management
- Product inventory management
- Order processing and fulfillment
- Revenue tracking
- Customer communication

### Customer Features (Frontend):
- Product browsing and search
- Store discovery
- Shopping cart management
- Order placement and tracking
- Account management

## Setup Verification

To verify the menu integration is working:

1. **Run the setup command:**
   ```bash
   php artisan ecommerce:setup
   ```

2. **Login as admin:**
   - Email: admin@kangoo.com
   - Password: password
   - Check for "E-commerce" section in sidebar

3. **Login as provider:**
   - Email: provider@kangoo.com
   - Password: password
   - Check for e-commerce items in provider section

4. **Test navigation:**
   - Click each menu item
   - Verify pages load correctly
   - Test CRUD operations

## Troubleshooting

### Menu Items Not Visible:
- Check user permissions
- Verify routes are properly defined
- Ensure language files are loaded

### Permission Errors:
- Verify user has appropriate permissions
- Check role assignments
- Review permission middleware

### Language Issues:
- Ensure language keys are defined in `messages.php`
- Check language file syntax
- Verify translation loading

## Support

For issues with the e-commerce menu integration:
1. Check the routes in `web.php`
2. Verify controllers exist and are properly namespaced
3. Ensure permissions are correctly assigned
4. Review language file entries
5. Test with sample data using the setup command
