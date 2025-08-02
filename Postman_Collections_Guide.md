# Kangoo API Postman Collections Guide

## 📁 Collection Files

1. **`Kangoo_Admin_API_Collection.json`** - Admin-specific endpoints
2. **`Kangoo_Customer_API_Collection.json`** - Customer-specific endpoints
3. **`Kangoo_Provider_API_Collection.json`** - Provider-specific endpoints

## 🚀 How to Import

1. Open Postman
2. Click **"Import"** button
3. Select each JSON file to import
4. Each collection will appear in your Postman workspace

## 🔧 Configuration

### Base URL

All collections are pre-configured with:

```
http://127.0.0.1:8001
```

### Authentication

Each collection has its own token variable:

- **Admin**: `{{admin_token}}`
- **Customer**: `{{customer_token}}`
- **Provider**: `{{provider_token}}`

## 📋 Collection Details

### 🔑 Admin Collection

**Purpose**: Complete system management and oversight

**Key Features:**

- ✅ **Store Management** - Configure main store settings, branding, images
- ✅ **Store Configuration** - Payment, shipping, email templates, tax settings
- ✅ **Product CRUD operations** (admin products)
- ✅ **Dynamic Pricing** - Advanced pricing strategies and analytics
- ✅ **Theme Management** - Brand colors, role-based theming, dynamic CSS
- ✅ **Product approval workflow** (approve/reject provider products)
- ✅ **Order management** (all orders)
- ✅ **Category management** - Product category CRUD
- ✅ **Bulk operations** - Efficient bulk actions
- ✅ **Analytics** - Store performance and analytics

**Main Sections:**

1. **🔐 Admin Authentication** - Login and dashboard
2. **🏪 Store Management** - Main store configuration and settings
3. **⚙️ Store Configuration** - Payment, shipping, email settings
4. **🛍️ Product Management** - Admin product CRUD
5. **💰 Dynamic Pricing** - Advanced pricing strategies and analytics
6. **🎨 Theme Management** - Brand colors, role-based theming, dynamic CSS
7. **✅ Product Approval** - Provider product approval workflow
8. **📦 Order Management** - System-wide order management
9. **🏷️ Category Management** - Product category CRUD

### 👤 Customer Collection

**Purpose**: Shopping and order management for end users

**Key Features:**

- ✅ Product browsing and search
- ✅ Direct purchase (no cart)
- ✅ Order tracking
- ✅ Payment processing
- ✅ User account management

**Main Sections:**

1. **🔐 Customer Authentication** - Registration, login, profile
2. **🏪 Store Browsing** - Main store information
3. **🛍️ Product Browsing** - Product catalog, search, categories
4. **🛒 Direct Purchase & Orders** - Order creation, tracking, cancellation
5. **💳 Payment** - Payment methods and processing
6. **🎨 Theme & Appearance** - Customer theme colors and styling

### 🏪 Provider Collection

**Purpose**: Product and order management for providers

**Key Features:**

- ✅ Product creation (requires admin approval)
- ✅ Product management (own products only)
- ✅ Order fulfillment
- ✅ Performance analytics
- ✅ Approval status tracking

**Main Sections:**

1. **🔐 Provider Authentication** - Registration, login, dashboard
2. **🛍️ Product Management** - Provider product CRUD
3. **📦 Order Management** - Provider order fulfillment
4. **📊 Analytics & Reports** - Performance tracking
5. **📋 Common Data** - Categories, locations
6. **🎨 Theme & Appearance** - Provider theme colors and styling

## 🔄 Workflow Examples

### Admin Workflow

1. **Login** → Get admin token
2. **Configure Store** → Set up main store settings, branding, payment methods
3. **Create Product** → Admin product (auto-approved)
4. **Review Pending** → Check provider products
5. **Approve/Reject** → Manage provider products
6. **Manage Orders** → Update order statuses
7. **Analytics** → Monitor store performance

### Customer Workflow

1. **Register/Login** → Get customer token
2. **Browse Products** → Search and filter
3. **Create Order** → Direct purchase
4. **Track Order** → Monitor status
5. **Payment** → Process payment

### Provider Workflow

1. **Register/Login** → Get provider token
2. **Create Product** → Submit for approval
3. **Check Status** → Monitor approval status
4. **Manage Orders** → Fulfill customer orders
5. **View Analytics** → Track performance

## 🎯 Key Differences from Original

### ✅ Improvements Made:

- **Role Separation**: Clean separation by user role
- **Single Store Architecture**: Removed multi-store endpoints
- **Approval Workflow**: Clear provider product approval process
- **Direct Purchase**: No cart system, direct order creation
- **Auto-token Management**: Login automatically saves tokens
- **Organized Structure**: Logical grouping by functionality

### ❌ Removed Endpoints:

- Multiple store management
- Store approval workflows
- Nearby stores (only one store exists)
- Cart-based shopping
- Provider store creation

## 🔐 Authentication Flow

### For Each Role:

1. Use the appropriate **Login** endpoint
2. Token is automatically saved to collection variable
3. All subsequent requests use the saved token
4. Token persists for the session

### Test Credentials:

- **Admin**: `admin@kangoo.com` / `password`
- **Customer**: Create via registration
- **Provider**: Create via registration

## � Dynamic Pricing Features

The system includes a comprehensive dynamic pricing engine for admin products:

### **Pricing Strategies:**

- **Lowest Price**: Automatically set to lowest provider price
- **Highest Price**: Automatically set to highest provider price
- **Fixed Price**: Admin sets a specific override price

### **Key Capabilities:**

- **Real-time Price Analysis** - Compare admin vs provider prices
- **Bulk Pricing Updates** - Update multiple products at once
- **Pricing Analytics** - Performance metrics and insights
- **Price Comparison Reports** - Detailed pricing analysis
- **Automated Pricing** - Dynamic price adjustments based on strategy

### **Admin Control:**

- Enable/disable dynamic pricing per product
- Set pricing strategies (lowest/highest/fixed)
- Override prices manually when needed
- Monitor pricing performance and analytics

### **API Endpoints:**

- `GET /api/dynamic-pricing` - List products with pricing info
- `POST /api/dynamic-pricing/update` - Update pricing strategy
- `POST /api/dynamic-pricing/bulk-update` - Bulk pricing operations
- `GET /api/dynamic-pricing/analytics` - Pricing analytics
- `POST /api/dynamic-pricing/price-comparison` - Price comparison reports

## �📝 Notes

- All collections use the same base URL but different endpoints
- Provider products require admin approval before being available
- Admin products are auto-approved and can use dynamic pricing
- Single store architecture means all products appear in one unified store
- Direct purchase model eliminates cart complexity
- Dynamic pricing only available for admin products

## 🎨 Theme Management Features

The system includes a comprehensive theme management system with role-based color theming:

### **Brand Colors:**

- **Yellow**: Light (#F0B521) / Dark (#8D6710)
- **Red**: Light (#EF5535) / Dark (#9B1F0B)
- **Green**: Light (#2DB665) / Dark (#005F2D)
- **Blue**: Light (#4A75FB) / Dark (#004CB2)

### **Role-Based Colors:**

- **Admin**: Purple (#5F60B9 / #4153b3)
- **Provider**: Red (#EF5535 / #9B1F0B)
- **Handyman**: Green (#2DB665 / #005F2D)
- **Customer**: Blue (#4A75FB / #004CB2)

### **Admin Capabilities:**

- **Update Theme Colors** - Modify brand and role colors
- **Preview Changes** - Test color combinations before applying
- **Create Defaults** - Reset to default color scheme
- **Generate Dynamic CSS** - Create CSS with current theme
- **Theme Versioning** - Track theme updates and changes

### **Customer/Provider Access:**

- **Get Theme Colors** - Retrieve current theme configuration
- **Role-Specific Themes** - Get colors specific to user role
- **Dynamic CSS** - Load CSS with current theme colors
- **Theme Updates** - Check for theme version changes

### **API Endpoints:**

- `GET /api/v1/theme/colors` - Get all theme colors
- `POST /theme-colors/update` - Update theme configuration (Admin)
- `GET /theme-colors/brand-colors` - Get brand colors
- `GET /theme-colors/role-colors` - Get role-based colors
- `POST /theme-colors/preview` - Preview theme changes
- `GET /css/theme-colors.css` - Dynamic CSS generation
- `POST /api/v1/theme/check-update` - Check theme version

### **Key Features:**

- **Dynamic CSS Generation** - Automatically generates CSS with current colors
- **Role-Based Theming** - Different color schemes for each user type
- **Real-time Updates** - Changes apply immediately across the platform
- **Version Control** - Track theme changes and updates
- **Preview Mode** - Test colors before applying changes

## 🚀 Ready to Use!

Import all three collections and start testing your Kangoo Service Platform APIs with proper role-based separation!
