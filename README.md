# Child Theme

A powerful WordPress child theme with built-in developer tools and advanced features for safe theme customization.

## Description

This is a comprehensive WordPress child theme that provides not only safe customization capabilities but also includes powerful developer tools for enhanced WordPress development workflow. Perfect for developers who need advanced features and client-friendly tools with a modular architecture.

## Features

### 🛠️ **Dev Tools Panel**
- **Admin Panel**: Custom developer panel with configurable tabs and views
- **User-Specific Access**: Show panel only for specified users/emails
- **Customizable Interface**: Override default panel titles, names, and views
- **Extensible Architecture**: Easy to add new tools and features

### 📋 **Available Modules**
- **Brand Settings**: Centralized brand management and customization
- **Login Page**: Complete login page customization with branding
- **Hide WP Login**: Security feature to hide WordPress login page
- **Menu Hider**: Hide specific admin menu items from users
- **Utilities**: Collection of useful development utilities including:
   - **Post Duplicator**: One-click duplication of posts, pages, and custom post types
   - Additional development and maintenance tools
- **Telegram Notify**: Integration with Telegram for notifications

### 🎨 **Advanced Form System**
- **Field Dependencies**: Dynamic field visibility based on other field values
- **Repeater Fields**: Sortable, collapsible repeater with dynamic titles
- **Media Integration**: WordPress media uploader with preview
- **Color Picker**: WordPress color picker integration
- **Grid Layout**: Responsive 12-column grid system for admin interfaces

### 🎯 **Developer Experience**
- **Modern PHP**: Namespaced classes and modern PHP practices
- **BaseModule Contract**: Standardized module interface
- **Autoloading**: PSR-4 compliant autoloader
- **Bootstrap System**: Clean initialization and configuration
- **Asset Management**: Organized CSS/JS with proper enqueuing
- **Update Server**: Built-in theme update mechanism (configurable)

## Requirements

- WordPress 6.8+
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
│       ├── bootstrap.php       # System bootstrap & module registration
│       ├── src/                # Core system source files
│       │   ├── Contracts/      # Interfaces and contracts
│       │   ├── Modules/        # Individual module implementations
│       │   │   ├── Brand/      # Brand settings module
│       │   │   ├── LoginPage/  # Login page customization
│       │   │   ├── HideWPLogin/ # Hide WP login module
│       │   │   ├── MenuHider/  # Menu hiding functionality
│       │   │   ├── Utilities/  # Development utilities (includes Post Duplicator)
│       │   │   └── TelegramNotify/ # Telegram notifications
│       │   └── Utils/          # Utility classes
│       ├── views/              # Template views for modules
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

### Available Modules

Each module provides specific functionality and can be individually configured:

- **Brand**: Centralized brand settings and customization options
- **LoginPage**: Complete WordPress login page customization
- **HideWPLogin**: Security feature to obscure WordPress login URL
- **MenuHider**: Selectively hide admin menu items from specific users
- **Utilities**: Collection of development and maintenance utilities including:
   - Post Duplicator with one-click duplication for posts, pages, and custom post types
   - Additional development tools and helpers
- **TelegramNotify**: Send notifications to Telegram channels/users

### Post Duplication (Utilities Module)

The Post Duplicator is integrated within the Utilities module. Simply go to Posts/Pages admin list and click the "Duplicate" link in row actions. The feature automatically:
- Creates an exact copy of the post
- Updates the title with "(Copy)" suffix
- Sets status to draft
- Preserves all meta data and taxonomies

### Using the Grid System

```html
<div class="grid-container">
    <div class="grid-column col-span-8">Main content</div>
    <div class="grid-column col-span-4">Sidebar</div>
</div>
```

## Creating Custom Modules

The dev tools system uses a contract-based modular architecture with the `BaseModule` interface:

### 1. Create Module Class

Create your module class in `inc/dev-tools/src/Modules/YourModule/`:

```php
<?php

namespace NeonWebId\DevTools\Modules\YourModule;

use NeonWebId\DevTools\Contracts\BaseModule;
use function __;

final class YourModule extends BaseModule
{
    public function id(): string
    {
        return 'your-module';
    }

    public function title(): string
    {
        return __('Your Module Title', 'dev-tools');
    }

    public function name(): string
    {
        return 'Your Module';
    }

    public function content(): void
    {
        $this->view->render('your-module/settings', [
            'field' => $this->field,
        ]);
    }

    public function apply(): void
    {
        $options = $this->option->get($this->id(), []);

        // Run handler only if at least one option is set
        if (!empty(array_filter($options))) {
            new YourModuleHandler($options);
        }
    }
}
```

### 2. Register Module

Add your module to the bootstrap in `inc/dev-tools/bootstrap.php`:

```php
protected function modules(): array
{
    return [
        Brand::class,
        LoginPage::class,
        HideWPLogin::class,
        MenuHider::class,
        Utilities::class,
        TelegramNotify::class,
        YourModule::class, // Add your module here
    ];
}
```

### 3. Create Module Handler (Optional)

Create a handler class for your module's functionality:

```php
<?php

namespace NeonWebId\DevTools\Modules\YourModule;

final class YourModuleHandler
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->init();
    }

    private function init(): void
    {
        // Initialize your module's functionality
        add_action('init', [$this, 'handleModuleActions']);
    }

    public function handleModuleActions(): void
    {
        // Your module logic here
    }
}
```

### 4. Create Module View

Create view files in `inc/dev-tools/views/your-module/`:

```php
<!-- inc/dev-tools/views/your-module/settings.php -->
<div class="grid-container">
    <div class="grid-column col-span-12">
        <h2><?php _e('Your Module Settings', 'dev-tools'); ?></h2>
        
        <?php echo $field->text([
            'id' => 'setting_name',
            'label' => __('Setting Name', 'dev-tools'),
            'description' => __('Description of this setting', 'dev-tools'),
        ]); ?>
        
    </div>
</div>
```

## Module Architecture

### BaseModule Contract

All modules must implement the `BaseModule` contract with these required methods:

- `id()`: Unique module identifier
- `title()`: Module title for admin display
- `name()`: Short module name
- `content()`: Render module's admin interface
- `apply()`: Execute module's functionality

### Module Structure

Each module should follow this structure:
```
src/Modules/YourModule/
├── YourModule.php          # Main module class
├── YourModuleHandler.php   # Module functionality handler
└── views/                  # Module-specific views (optional)
```

## Development

### Theme Updates

The theme includes a built-in update mechanism. To enable:

```php
// In bootstrap.php onConstruct() method
$this->updater->setUpdateServer('https://your-update-server.com/')
    ->setHeaders([
        'Authorization' => 'Bearer ' . get_option('your_api_key', ''),
        'X-Theme-Slug' => get_stylesheet(),
    ])
    ->sslVerify(true)
    ->setTimeout(30)
    ->setParams([
        'slug' => get_stylesheet(),
    ])
    ->setThemeData(wp_get_theme(get_stylesheet()));
```

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
- **Last Updated**: July 31, 2025 07:35 UTC
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

*A professional WordPress child theme with developer tools - Updated: July 31, 2025 07:35 UTC*