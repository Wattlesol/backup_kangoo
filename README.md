# Kangoo Service Platform

A comprehensive service platform with integrated e-commerce features, built with Laravel and Vue.js.

## Features

### Core Platform
- **Service Management**: Complete service booking and management system
- **Provider Network**: Multi-provider service delivery system
- **User Management**: Customer, provider, and admin user roles
- **Booking System**: Advanced booking with status tracking
- **Rating & Reviews**: Customer feedback and rating system
- **Payment Integration**: Multiple payment gateway support

### E-commerce Integration
- **Product Management**: Full product catalog with categories
- **Store Management**: Provider-owned stores with approval system
- **Inventory Tracking**: Stock management and availability
- **Order Management**: Complete order processing workflow
- **Dynamic Pricing**: Admin override capabilities
- **Multi-vendor Support**: Providers can create and manage stores

### Frontend Features
- **Responsive Design**: Mobile-first responsive interface
- **Landing Page**: Customizable sections and content
- **Search & Filtering**: Advanced product and service search
- **Location-based Services**: Nearest provider matching
- **Multi-language Support**: Internationalization ready
- **Dark Mode**: Theme switching capability

### Admin Panel
- **Dashboard**: Comprehensive analytics and reporting
- **Content Management**: Frontend section customization
- **User Management**: Role-based access control
- **Settings**: System configuration and preferences
- **Reports**: Business intelligence and insights

## Technology Stack

- **Backend**: Laravel 8.x
- **Frontend**: Vue.js 2.x with Bootstrap 5
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **File Storage**: Laravel Storage
- **Queue System**: Laravel Queues
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

### Frontend Settings
Configure frontend sections through the admin panel:
- Hero section content
- Service categories
- Provider information
- Contact details
- Social media links

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is proprietary software. All rights reserved.

## Support

For support and questions, please contact the development team.

---

**Built with ❤️ for service excellence**
