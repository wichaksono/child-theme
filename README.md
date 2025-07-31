# Child Theme

A powerful WordPress child theme with built-in developer tools and advanced features for safe theme customization.

## Description

This is a comprehensive WordPress child theme that provides not only safe customization capabilities but also includes powerful developer tools for enhanced WordPress development workflow. Perfect for developers who need advanced features and client-friendly tools.

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
- **Modular Architecture**: Easy to create and integrate custom modules

## Requirements

- WordPress 5.0+
- PHP 8.2+
- Compatible parent theme (customize Template field in style.css)

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
     Template: your-parent-theme
     Version: 1.0.0
     Text Domain: your-text-domain
     */
     ```
   - Replace `your-parent-theme` with the actual parent theme folder name

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
├── .gitignore                    # Git ignore file
├── style.css                    # Child theme stylesheet (update this)
├── functions.php                # Main functions with dev tools integration
├── screenshot.png               # Theme screenshot
├── README.md                    # Documentation
├── inc/                         # Include files
│   └── dev-tools/               # Developer tools system
│       ├── autoload.php        # PSR-4 autoloader
│       ├── bootstrap.php       # System bootstrap
│       ├── src/                # Module source files
│       ├── views/              # Template views
│       └── assets/
│           ├── css/
│           │   └── admin.css   # 12-column grid + field styling
│           └── js/
│               ├── admin.js    # Main admin functionality
│               ├── repeater-field.js   # Repeater field logic
│               └── field-dependencies.js # Field dependency handler
└── js/                         # Additional JavaScript files
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

## Creating Custom Modules

The dev tools system supports a modular architecture that makes it easy to add custom functionality:

### 1. Create Module Class

Create your module class in `inc/dev-tools/src/Modules/`:

```php
<?php
namespace NeonWebId\DevTools\Modules;

class YourCustomModule {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    
    public function addAdminMenu() {
        // Add your admin menu logic
    }
    
    public function enqueueAssets() {
        // Enqueue your CSS/JS assets
    }
}
```

### 2. Register Module

Add your module to the autoloader in `inc/dev-tools/autoload.php`:

```php
// Register your module class
$classMap['NeonWebId\\DevTools\\Modules\\YourCustomModule'] = __DIR__ . '/src/Modules/YourCustomModule.php';
```

### 3. Initialize Module

Initialize your module in `inc/dev-tools/bootstrap.php`:

```php
use NeonWebId\DevTools\Modules\YourCustomModule;

// Initialize your module
new YourCustomModule();
```

### 4. Add Module Views (Optional)

Create view files in `inc/dev-tools/views/` for your module's admin interface:

```php
// inc/dev-tools/views/your-module-view.php
<div class="grid-container">
    <div class="grid-column col-span-12">
        <h2><?php _e('Your Custom Module', 'your-text-domain'); ?></h2>
        <!-- Your module content -->
    </div>
</div>
```

### 5. Module Assets

Add module-specific CSS/JS in `inc/dev-tools/assets/`:
- CSS: `inc/dev-tools/assets/css/your-module.css`
- JS: `inc/dev-tools/assets/js/your-module.js`

## Development

### Adding Custom Tools

1. Create your tool class in `inc/dev-tools/src/Modules/`
2. Register in the autoloader
3. Initialize in bootstrap.php
4. Add views in `inc/dev-tools/views/`
5. Enqueue assets if needed

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
- **Last Updated**: July 31, 2025 07:30 UTC
- **Primary Language**: PHP (90.9%)
- **Secondary Languages**: JavaScript (4.6%), CSS (4.5%)
- **Customizable Parent Theme**: Update Template field in style.css
- **Current Version**: 1.0.0

## Author

**Wichaksono** ([@wichaksono](https://github.com/wichaksono))
- Repository: [wichaksono/child-theme](https://github.com/wichaksono/child-theme)

## License

This project is open source. No specific license has been set for this repository.

---

*A professional WordPress child theme with developer tools - Updated: July 31, 2025 07:30 UTC*