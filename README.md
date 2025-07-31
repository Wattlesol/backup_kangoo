# Kangoo Service Platform

A comprehensive service booking and e-commerce platform built with Laravel, featuring service management, direct product purchases, and multi-user functionality.

## 🚀 Features

### Service Management

- **Service Booking**: Complete booking system with time slots and provider management
- **Provider Network**: Multi-provider service marketplace
- **Service Categories**: Organized service catalog with filtering
- **Booking Management**: Full lifecycle from booking to completion

### E-commerce System

- **Direct Purchase**: One-click product buying without cart complexity
- **Multi-seller Support**: Admin and provider products in unified store
- **Product Approval**: Admin approval workflow for provider products
- **Order Management**: Complete order tracking and fulfillment
- **Payment Integration**: Stripe and wallet payments

### User Management

- **Role-based Access**: Admin, Provider, Customer roles with proper permissions
- **Authentication**: Secure login system with Laravel Sanctum
- **User Dashboards**: Customized interfaces for each user type
- **Profile Management**: Complete user account management

## 🏗️ Architecture

### Technology Stack

- **Backend**: Laravel 9.x with PHP 8.1+
- **Database**: MySQL with optimized schema
- **Frontend**: Blade templates with Vue.js components
- **Authentication**: Laravel Sanctum
- **Payments**: Stripe integration with wallet system
- **APIs**: RESTful API architecture

### Database Design

```
Core Tables:
├── users (admin, providers, customers)
├── services (service catalog)
├── bookings (service bookings)
├── products (e-commerce products)
├── orders (product orders)
├── payments (payment tracking)
└── stores (unified store management)
```

- **Caching**: Redis/File-based caching

## Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/YOUR_USERNAME/kangoo-service-platform.git
   cd kangoo-service-platform
   ```

2. **Install dependencies**

   ```bash
   composer install
   npm install
   ```

3. **Environment setup**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**

   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Build assets**

   ```bash
   npm run dev
   # or for production
   npm run production
   ```

6. **Start the application**
   ```bash
   php artisan serve
   ```

## Configuration

### Database

Update your `.env` file with database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kangoo_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Product Approval System

The platform includes a comprehensive product approval workflow:

#### Admin Features:

- **Pending Products**: Review provider-submitted products awaiting approval
- **Rejected Products**: Manage rejected products with reconsideration options
- **Bulk Actions**: Approve, reject, or reconsider multiple products at once
- **Provider Filtering**: Filter products by specific providers
- **DataTables Integration**: Server-side processing for large datasets

#### Workflow:

1. **Admin Products**: Auto-approved and appear directly in Products tab
2. **Provider Products**: Require admin approval before being listed
3. **Approval Process**: Admin can approve, reject with reason, or request changes

## 🔧 Key Components

### Controllers

- `BookingController`: Service booking management
- `Frontend/ProductController`: Direct product purchases
- `Provider/OrderController`: Provider order management
- `ProductPaymentController`: Payment processing

### Models

- `Booking`: Service booking lifecycle
- `Order`: Product order management
- `Product`: Product catalog with approval system
- `User`: Multi-role user management

### APIs

- Service booking and management
- Product catalog and ordering
- Payment processing
- User account management

## 📈 Performance Features

- **Direct Purchase Model**: Higher conversion rates
- **Unified Architecture**: Simplified database queries
- **Mobile Optimized**: Responsive design for all devices
- **Efficient Routing**: Smart order and booking assignment

## 🔒 Security

- Laravel's built-in security features
- Role-based access control
- Input validation and sanitization
- Secure payment processing
- API authentication with Sanctum

## 🚀 Recent Improvements

- **Direct Purchase System**: Streamlined product buying
- **Unified Store**: Single storefront for all products
- **Enhanced Order Management**: Complete order lifecycle
- **Payment Integration**: Multiple payment methods
- **Mobile Optimization**: Improved mobile experience

## 📞 Support

For technical support or questions:

- Check documentation first
- Create detailed issue reports
- Include steps to reproduce problems
- Provide relevant system information

---

**Version**: 2.0.0
**Last Updated**: July 30, 2025
**Status**: Production Ready
**Architecture**: Service + E-commerce Platform
