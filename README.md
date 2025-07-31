# Child Theme

A powerful WordPress child theme with built-in developer tools and advanced features for safe theme customization.

## Description

This is a comprehensive WordPress child theme built on GeneratePress that provides not only safe customization capabilities but also includes powerful developer tools for enhanced WordPress development workflow. Perfect for developers who need advanced features and client-friendly tools.

## Features

### 🛠️ **Dev Tools Panel**
- **Admin Panel**: Custom developer panel with configurable tabs and views
- **User-Specific Access**: Show panel only for specified users/emails
- **Customizable Interface**: Override default panel titles, names, and views
- **Extensible Architecture**: Easy to add new tools and features

### 📋 **Post Duplicator**
- **One-Click Duplication**: Duplicate posts, pages, and custom post types
- **Admin Integration**: "Duplicate" link added to row actions
- **Security First**: Nonce verification and permission checking
- **Namespace Support**: Clean `Wichaksono\WordPress\Features` namespace

### 🎨 **Advanced Form System**
- **Field Dependencies**: Dynamic field visibility based on other field values
- **Repeater Fields**: Sortable, collapsible repeater with dynamic titles
- **Media Integration**: WordPress media uploader with preview
- **Color Picker**: WordPress color picker integration
- **Grid Layout**: Responsive 12-column grid system for admin interfaces

### 🎯 **Developer Experience**
- **Modern PHP**: Namespaced classes and modern PHP practices
- **Autoloading**: PSR-4 compliant autoloader
- **Bootstrap System**: Clean initialization and configuration
- **Asset Management**: Organized CSS/JS with proper enqueuing

## Requirements

- WordPress 5.0+
- PHP 8.2+
- GeneratePress parent theme

## Installation & Setup

1. **Download the theme**
   ```bash
   git clone https://github.com/wichaksono/child-theme.git
   ```

2. **Customize for your project**
   - Rename the `child-theme` folder to your desired theme name
   - Edit `style.css` and update the theme information:
     ```css
     /*
     Theme Name: Your Theme Name
     Description: Your theme description
     Author: Your Name
     Author URI: https://yourwebsite.com/
     Template: generatepress
     Version: 1.0.0
     Text Domain: your-text-domain
     */
     ```

3. **Upload to WordPress**
   - Upload the renamed folder to `/wp-content/themes/` directory
   - Or use WordPress admin: Appearance > Themes > Add New > Upload Theme

4. **Activate the theme**
   - Go to WordPress Admin > Appearance > Themes
   - Find your child theme and click "Activate"

5. **Configure Dev Tools** (Optional)
   ```php
   // In functions.php, uncomment and configure:
   $devTools->showPanelFor([
       'admin@example.com',
       'developer',
   ]);
   ```

## File Structure

```
your-theme-name/
├── style.css                     # Child theme stylesheet (update this)
├── functions.php                 # Main functions with dev tools integration
├── inc/
│   ├── dev-tools/                # Developer tools system
│   │   ├── autoload.php         # PSR-4 autoloader
│   │   ├── bootstrap.php        # System bootstrap
│   │   └── assets/
│   │       ├── css/
│   │       │   └── admin.css    # 12-column grid + field styling
│   │       └── js/
│   │           ├── admin.js     # Main admin functionality
│   │           ├── repeater-field.js    # Repeater field logic
│   │           └── field-dependencies.js # Field dependency handler
│   └── PostDuplicator.php       # Post duplication feature
```

## Usage

### Dev Tools Panel Configuration

```php
// Enable dev tools for specific users
$devTools->showPanelFor([
    'admin@example.com',
    'developer',
]);

// Customize panel appearance
$devTools->setGeneralTab([
    'title' => __('Development Tools', 'your-text-domain'),
    'name'  => 'Dev Tools',
    'view'  => 'custom-view',
]);
```

### Post Duplication

Simply go to Posts/Pages admin list and click the "Duplicate" link in row actions. The feature automatically:
- Creates an exact copy of the post
- Updates the title with "(Copy)" suffix
- Sets status to draft
- Preserves all meta data and taxonomies

### Form Field Dependencies

```php
// Field will only show if 'enable_feature' field is checked
$field->addDependency('enable_feature', '==', ['1']);
```

### Using the Grid System

```html
<div class="grid-container">
    <div class="grid-column col-span-8">Main content</div>
    <div class="grid-column col-span-4">Sidebar</div>
</div>
```

## Development

### Adding Custom Tools

1. Create your tool class in `inc/dev-tools/src/`
2. Register in the autoloader
3. Initialize in bootstrap.php
4. Add views in `inc/dev-tools/views/`

### JavaScript Development

All admin JavaScript is properly organized:
- `admin.js` - Main admin functionality
- `repeater-field.js` - Repeater field management
- `field-dependencies.js` - Dynamic field visibility

## Documentation

For complete documentation, features, and advanced usage examples, please visit the [repository](https://github.com/wichaksono/child-theme) for the most up-to-date information.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Repository Information

- **Created**: July 30, 2025
- **Last Updated**: July 31, 2025
- **Primary Language**: PHP (90.9%)
- **Secondary Languages**: JavaScript (4.6%), CSS (4.5%)
- **Parent Theme**: GeneratePress
- **Current Version**: 1.0.0

## Author

**Wichaksono** ([@wichaksono](https://github.com/wichaksono))
- Repository: [wichaksono/child-theme](https://github.com/wichaksono/child-theme)

## License

This project is open source. No specific license has been set for this repository.

---

*A professional WordPress child theme with developer tools - Updated: July 31, 2025*