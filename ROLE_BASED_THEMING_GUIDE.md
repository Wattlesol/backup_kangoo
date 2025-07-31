# Role-Based Color Theming System

This guide explains how to use the newly implemented role-based color theming system in the application.

## Overview

The application now supports dynamic color theming based on user roles:

- **Customer App**: Blue (#4A75FB light / #004CB2 dark)
- **Admin App**: Purple (#5F60B9 light / #4153b3 dark) - *unchanged*
- **Handyman App**: Green (#2DB665 light / #005F2D dark)
- **Provider App**: Red (#EF5535 light / #9B1F0B dark)

## Files Modified

### Backend Files
1. `app/Helper/helper.php` - Added theme helper functions
2. `app/View/Composers/ThemeComposer.php` - New view composer for theme data
3. `app/Providers/AppServiceProvider.php` - Registered view composer
4. `public/scss/_variable.scss` - Added brand color variables

### Frontend Files
1. `public/css/provide.css` - Added role-based CSS classes
2. `public/js/role-based-theming.js` - New JavaScript theming system
3. `resources/views/components/master-layout.blade.php` - Updated with theme classes
4. `resources/views/layouts/dashboard.blade.php` - Updated with theme classes
5. `resources/views/partials/_head.blade.php` - Added theming script
6. `resources/views/components/rotating-card.blade.php` - New card component

## How It Works

### 1. Automatic Role Detection
The system automatically detects the logged-in user's role and applies the appropriate theme:

```php
// Helper function in app/Helper/helper.php
function getUserRoleTheme() {
    // Returns theme data based on user role
}
```

### 2. CSS Variables
Role-based CSS classes override the primary color variables:

```css
.theme-customer {
    --c1: #4A75FB;
    --link-color: #4A75FB;
}

.theme-provider {
    --c1: #EF5535;
    --link-color: #EF5535;
}
```

### 3. JavaScript Integration
The JavaScript system handles dynamic theme switching and card colors:

```javascript
// Available globally
window.RoleTheming.getCurrentRoleColors()
window.RoleTheming.getRotatingCardColor(index)
```

## Usage Examples

### 1. Using Rotating Card Colors

#### Method A: Using the Blade Component
```blade
<x-rotating-card :index="0" class="mb-3">
    <h5>Card Title</h5>
    <p>Card content here</p>
</x-rotating-card>

<x-rotating-card :index="1" :color-element="true">
    <h5>Card with Color Header</h5>
</x-rotating-card>
```

#### Method B: Manual Implementation
```blade
@foreach($items as $index => $item)
    <div class="card" data-card-index="{{ $index }}">
        <div class="card-body">
            <h5>{{ $item->title }}</h5>
        </div>
    </div>
@endforeach
```

#### Method C: Using CSS Classes
```blade
<div class="card rotating-card-color">
    <div class="card-body">
        Content here
    </div>
</div>
```

### 2. Getting Current Theme Colors in JavaScript

```javascript
// Get current user's theme colors
const colors = window.RoleTheming.getCurrentRoleColors();
console.log(colors.primary); // Current primary color
console.log(colors.role);    // User role
console.log(colors.isDark);  // Dark mode status

// Get specific brand color
const blueColor = window.RoleTheming.getBrandColor('blue', false); // Light blue
const redColorDark = window.RoleTheming.getBrandColor('red', true); // Dark red
```

### 3. Applying Colors Programmatically

```javascript
// Apply rotating card colors manually
window.RoleTheming.applyRotatingCardColors();

// Get rotating color for specific index
const color = window.RoleTheming.getRotatingCardColor(2); // Third color in rotation
```

### 4. Using Theme Data in Blade Templates

```blade
<!-- Access theme data in any view -->
<div style="background-color: {{ $userTheme['primary_light'] }};">
    Primary color background
</div>

<!-- Check user role -->
@if($userTheme['role'] === 'admin')
    <p>Admin-specific content</p>
@endif

<!-- Use brand colors -->
@php $yellowColor = $brandColors['yellow']['light']; @endphp
<div style="color: {{ $yellowColor }};">Yellow text</div>
```

## Brand Color Rotation

Cards in grid layouts automatically rotate through all brand colors:

1. **Index 0**: Yellow (#F0B521 / #8D6710)
2. **Index 1**: Red (#EF5535 / #9B1F0B)
3. **Index 2**: Green (#2DB665 / #005F2D)
4. **Index 3**: Blue (#4A75FB / #004CB2)
5. **Index 4**: Yellow (repeats)

## Dark Mode Compatibility

The system automatically adapts to light/dark mode changes:

- Light mode uses the light brand colors
- Dark mode uses the dark brand colors
- Theme switching is handled automatically
- All role-based colors have dark mode variants

## Accessibility

All color combinations have been designed with accessibility in mind:

- Sufficient contrast ratios for text readability
- Color combinations tested for color blindness
- Fallback colors for unsupported browsers

## Customization

### Adding New Roles

1. Add role colors to `getUserRoleTheme()` in `app/Helper/helper.php`
2. Add CSS classes in `public/css/provide.css`
3. Update JavaScript `roleThemes` object in `public/js/role-based-theming.js`

### Modifying Brand Colors

1. Update color variables in `public/scss/_variable.scss`
2. Update `getBrandColors()` function in `app/Helper/helper.php`
3. Update JavaScript `brandColors` object

## Troubleshooting

### Theme Not Applying
- Check if user is logged in
- Verify role assignment
- Clear browser cache
- Check console for JavaScript errors

### Colors Not Rotating
- Ensure `data-card-index` attribute is set
- Check if JavaScript file is loaded
- Verify `window.RoleTheming` is available

### Dark Mode Issues
- Check `data-bs-theme` attribute on `<html>`
- Verify CSS dark mode selectors
- Test theme switching functionality

## Browser Support

- Modern browsers (Chrome 60+, Firefox 55+, Safari 12+)
- CSS custom properties support required
- JavaScript ES6+ features used
- Graceful degradation for older browsers
