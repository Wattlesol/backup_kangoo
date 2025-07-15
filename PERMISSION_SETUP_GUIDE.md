# E-Commerce Permission Setup Guide

## Quick Fix for Permission Issues

If you're getting "USER DOES NOT HAVE THE RIGHT PERMISSIONS" error, run this command:

```bash
php artisan ecommerce:permissions
```

This will set up all the necessary permissions for the e-commerce system.

## Detailed Permission Structure

### Admin Permissions
Admins get **full access** to all e-commerce features:

#### Product Categories
- ✅ `product_category list` - View categories
- ✅ `product_category add` - Create categories  
- ✅ `product_category edit` - Edit categories
- ✅ `product_category delete` - Delete categories

#### Products
- ✅ `product list` - View all products
- ✅ `product add` - Create products
- ✅ `product edit` - Edit any product
- ✅ `product delete` - Delete any product
- ✅ `product view` - View product details

#### Stores
- ✅ `store list` - View all stores
- ✅ `store add` - Create stores for providers
- ✅ `store edit` - Edit any store
- ✅ `store delete` - Delete stores
- ✅ `store view` - View store details
- ✅ `store approve` - Approve/reject store applications
- ✅ `store suspend` - Suspend stores

#### Orders
- ✅ `order list` - View all orders
- ✅ `order add` - Create orders
- ✅ `order edit` - Edit orders
- ✅ `order delete` - Delete orders
- ✅ `order view` - View order details
- ✅ `order status update` - Update order status

#### Dynamic Pricing (Admin Only)
- ✅ `dynamic_pricing list` - View pricing dashboard
- ✅ `dynamic_pricing edit` - Manage pricing strategies
- ✅ `dynamic_pricing analytics` - View pricing analytics

#### Provider Management
- ✅ `provider_store manage` - Manage provider stores
- ✅ `provider_product manage` - Manage provider products
- ✅ `provider_order manage` - Manage provider orders

### Provider Permissions
Providers get **limited access** to manage their own business:

#### Product Categories
- ✅ `product_category list` - View categories (to assign products)

#### Products
- ✅ `product list` - View products
- ✅ `product add` - Create own products
- ✅ `product edit` - Edit own products
- ✅ `product delete` - Delete own products
- ✅ `product view` - View product details

#### Stores
- ✅ `store list` - View stores
- ✅ `store add` - Create own store
- ✅ `store edit` - Edit own store
- ✅ `store view` - View store details
- ❌ `store approve` - Cannot approve stores
- ❌ `store suspend` - Cannot suspend stores

#### Orders
- ✅ `order list` - View own orders
- ✅ `order view` - View order details
- ✅ `order status update` - Update order status
- ❌ `order add` - Cannot create orders manually
- ❌ `order edit` - Cannot edit orders
- ❌ `order delete` - Cannot delete orders

#### Provider-Specific
- ✅ `provider_store manage` - Manage own store
- ✅ `provider_product manage` - Manage own products
- ✅ `provider_order manage` - Manage own orders

#### Restrictions
- ❌ No access to dynamic pricing
- ❌ Cannot approve/reject other stores
- ❌ Cannot view other providers' data
- ❌ Cannot manage admin products

## Business Logic Implementation

### Store Management
1. **Admin can create stores for providers**
   - Admin assigns store to specific provider
   - Store status starts as "approved" when created by admin

2. **Providers can create their own stores**
   - Store status starts as "pending"
   - Requires admin approval before activation
   - Only one store per provider allowed

3. **Store approval workflow**
   - Admin receives notification when provider creates store
   - Admin can approve, reject, or suspend stores
   - Providers receive notification of status changes

### Product Management
1. **Admin products**
   - Created by admin users
   - Automatically available in admin store
   - Can be added to any provider store
   - Subject to dynamic pricing

2. **Provider products**
   - Created by provider users
   - Only available in their own store
   - Provider sets pricing and inventory
   - Not subject to dynamic pricing

3. **Product access control**
   - Admin can edit any product
   - Providers can only edit their own products
   - Admin can add/remove products from any store
   - Providers can only manage their store inventory

### Order Management
1. **Order routing logic**
   - Orders route to nearest provider first
   - Falls back to admin if no local provider has product
   - Admin can see all orders
   - Providers only see their store orders

2. **Order status updates**
   - Admin can update any order status
   - Providers can update their own order status
   - Automatic notifications sent on status changes
   - Status history tracked for audit

### Dynamic Pricing (Admin Only)
1. **Pricing strategies**
   - Lowest: Admin price always matches or beats lowest store price
   - Highest: Admin price always matches or exceeds highest store price
   - Fixed: Admin sets specific override price

2. **Price management**
   - Only admin can access dynamic pricing
   - Applies only to admin products
   - Real-time price comparison tools
   - Bulk pricing operations

## Setup Commands

### Full E-commerce Setup
```bash
# Complete setup with sample data
php artisan ecommerce:setup --fresh
```

### Permissions Only
```bash
# Just setup permissions
php artisan ecommerce:permissions
```

### Manual Permission Setup
```bash
# Run permission seeder directly
php artisan db:seed --class=EcommercePermissionSeeder
```

## Troubleshooting

### Permission Issues
1. **Menu items not visible**
   - Run: `php artisan ecommerce:permissions`
   - Clear cache: `php artisan cache:clear`
   - Logout and login again

2. **Access denied errors**
   - Verify user has correct role assigned
   - Check if permissions were created
   - Ensure role-permission assignments are correct

3. **Provider features not working**
   - Verify user has 'provider' role
   - Check if provider-specific permissions exist
   - Ensure provider routes are accessible

### Database Issues
1. **Permission table errors**
   - Ensure Spatie Permission package is installed
   - Run: `php artisan migrate`
   - Check if permission tables exist

2. **Role assignment issues**
   - Verify users have correct user_type
   - Check role assignments in database
   - Re-run permission seeder if needed

## Verification Steps

### For Admin Users
1. Login to admin panel
2. Check for "E-commerce" section in sidebar
3. Verify access to all menu items:
   - Product Categories
   - Products
   - Stores
   - Orders
   - Dynamic Pricing

### For Provider Users
1. Login to provider panel
2. Check for e-commerce items in provider section:
   - My Store
   - My Products
   - My Orders
3. Verify no access to admin-only features

### Test Permissions
```bash
# Create test users and verify permissions
php artisan tinker

# Check admin permissions
$admin = User::where('user_type', 'admin')->first();
$admin->can('product list'); // Should return true
$admin->can('dynamic_pricing list'); // Should return true

# Check provider permissions  
$provider = User::where('user_type', 'provider')->first();
$provider->can('provider_store manage'); // Should return true
$provider->can('dynamic_pricing list'); // Should return false
```

## Security Notes

1. **Role-based access control**
   - All routes protected by permission middleware
   - Users can only access features they have permissions for
   - Provider isolation enforced at controller level

2. **Data isolation**
   - Providers can only see their own data
   - Admin has full visibility
   - Cross-provider data access prevented

3. **Feature restrictions**
   - Dynamic pricing is admin-exclusive
   - Store approval requires admin privileges
   - Order management respects ownership rules

## Support

If you continue to have permission issues:

1. Check the permission seeder output for errors
2. Verify database tables exist and are populated
3. Ensure user roles are correctly assigned
4. Clear all caches and try again
5. Check Laravel logs for detailed error messages

The permission system is designed to be secure by default while providing the flexibility needed for a multi-tenant e-commerce platform.
