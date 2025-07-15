# E-Commerce Menu Structure Visual Guide

## Admin Sidebar Menu Structure

```
📊 Dashboard
📅 Booking
   ├── 📋 Booking List
   ├── ➕ Create Booking
   └── 📦 Package Booking

🛒 E-COMMERCE                    ← NEW SECTION
   ├── 🏷️ Product Categories     → /productcategory
   ├── 📦 Products               → /product  
   ├── 🏪 Stores                 → /store
   ├── 📋 Orders                 → /order
   └── 💰 Dynamic Pricing        → /dynamic-pricing

⏰ Time
🏙️ City  
🌍 Region
🏘️ Districts
💰 Price List
👥 Users
   ├── 👨‍💼 Providers
   ├── 🔧 Handymen
   └── 👤 Customers
⚙️ Settings
```

## Provider Sidebar Menu Structure

```
📊 Dashboard
📅 Booking
   ├── 📋 My Bookings
   └── 📦 Package Booking
   
   🛒 E-COMMERCE FEATURES        ← NEW PROVIDER SECTION
   ├── 🏪 My Store              → /provider/store
   ├── 📦 My Products            → /provider/product
   └── 📋 My Orders              → /provider/orders

⚙️ Settings
👤 Profile
```

## Menu Item Details

### Admin E-Commerce Section

#### 🏷️ Product Categories
- **Route**: `/productcategory`
- **Permission**: `product_category list`
- **Features**:
  - Create/Edit/Delete categories
  - Set featured categories
  - Manage category hierarchy
  - Bulk operations

#### 📦 Products  
- **Route**: `/product`
- **Permission**: `product list`
- **Features**:
  - Manage all products (admin + provider)
  - Create/Edit/Delete products
  - Bulk operations
  - Product variants
  - Stock management

#### 🏪 Stores
- **Route**: `/store`
- **Permission**: `store list`
- **Features**:
  - Approve/Reject store applications
  - View all stores
  - Store performance analytics
  - Manage store settings

#### 📋 Orders
- **Route**: `/order`
- **Permission**: `order list`
- **Features**:
  - View all orders from all stores
  - Update order statuses
  - Order analytics
  - Export functionality
  - Bulk operations

#### 💰 Dynamic Pricing
- **Route**: `/dynamic-pricing`
- **Permission**: `product list`
- **Features**:
  - Advanced pricing strategies
  - Price comparison tools
  - Bulk pricing updates
  - Analytics dashboard
  - Competitive pricing

### Provider E-Commerce Section

#### 🏪 My Store
- **Route**: `/provider/store`
- **Features**:
  - Create store application
  - Edit store information
  - View approval status
  - Store analytics
  - Delivery settings

#### 📦 My Products
- **Route**: `/provider/product`
- **Features**:
  - Create provider products
  - Manage inventory
  - Set store-specific pricing
  - Add products to store
  - Stock management

#### 📋 My Orders
- **Route**: `/provider/orders`
- **Features**:
  - View store orders
  - Update order status
  - Process deliveries
  - Order analytics
  - Customer communication

## Visual Menu Appearance

### Admin Menu Item Example:
```
🛒 E-COMMERCE
   ├── 🏷️ Product Categories    [Badge: 15]
   ├── 📦 Products              [Badge: 142]
   ├── 🏪 Stores                [Badge: 8 Pending]
   ├── 📋 Orders                [Badge: 23 New]
   └── 💰 Dynamic Pricing       [Badge: Active]
```

### Provider Menu Item Example:
```
🏪 My Store                     [Status: Approved]
📦 My Products                  [Count: 12]
📋 My Orders                    [New: 3]
```

## Menu States & Indicators

### Store Status Indicators:
- 🟢 **Approved**: Store is active and can receive orders
- 🟡 **Pending**: Store application under review
- 🔴 **Rejected**: Store application rejected

### Order Status Indicators:
- 🔵 **New Orders**: Unprocessed orders
- 🟡 **Processing**: Orders being prepared
- 🟢 **Completed**: Successfully delivered orders
- 🔴 **Cancelled**: Cancelled orders

### Product Status Indicators:
- 🟢 **In Stock**: Available products
- 🟡 **Low Stock**: Products below threshold
- 🔴 **Out of Stock**: Unavailable products

## Responsive Behavior

### Desktop View:
- Full menu with icons and text
- Tooltips on hover
- Expandable sections
- Badge notifications

### Mobile View:
- Collapsible sidebar
- Icon-only view when collapsed
- Touch-friendly navigation
- Swipe gestures

### Tablet View:
- Condensed menu layout
- Icons with abbreviated text
- Touch optimization
- Landscape/portrait adaptation

## Accessibility Features

### Keyboard Navigation:
- Tab navigation support
- Arrow key menu traversal
- Enter/Space activation
- Escape to close menus

### Screen Reader Support:
- ARIA labels for all menu items
- Role definitions
- State announcements
- Descriptive tooltips

### Visual Indicators:
- High contrast icons
- Clear focus states
- Color-blind friendly badges
- Consistent spacing

## Menu Customization

### Admin Customization:
- Show/hide menu sections based on permissions
- Reorder menu items
- Custom menu labels
- Badge configuration

### Provider Customization:
- Personalized dashboard links
- Quick action shortcuts
- Notification preferences
- Menu collapse settings

## Integration Points

### With Existing Features:
- User management integration
- Permission system compatibility
- Notification system integration
- Search functionality
- Multi-language support

### With New Features:
- Real-time order updates
- Live inventory tracking
- Dynamic pricing alerts
- Performance analytics
- Mobile app synchronization
