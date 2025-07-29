# WordPress Theme Updater

A complete WordPress theme updater system that allows themes to receive updates from a private server without relying on WordPress.org repository.

## Features

- ✅ Automatic update checks every 12 hours
- ✅ Manual update triggers via admin interface
- ✅ Secure token-based file downloads
- ✅ Detailed theme information display
- ✅ Server status monitoring
- ✅ Update caching for performance optimization
- ✅ No license dependency (simplified implementation)
- ✅ Fluent interface for easy configuration

## Installation

### 1. Add ThemeUpdater to Your Theme

Copy the `ThemeUpdater.php` file to your theme's includes directory and include it in your `functions.php`:

```php
require_once get_template_directory() . '/includes/ThemeUpdater.php';

use NeonWebId\DevTools\Utils\ThemeUpdater\ThemeUpdater;

function init_theme_updater() {
    if (!is_admin()) return;

    ThemeUpdater::create()
        ->setThemeSlug(get_template())
        ->setVersion(wp_get_theme()->get('Version'))
        ->setUpdateServer('https://your-update-server.com/')
        ->init();
}
add_action('init', 'init_theme_updater');
```

### 2. Add Admin Interface (Optional)

```php
function theme_updater_admin_menu() {
    add_theme_page(
        'Theme Updates',
        'Theme Updates', 
        'manage_options',
        'theme-updater',
        'theme_updater_admin_page'
    );
}
add_action('admin_menu', 'theme_updater_admin_menu');
```

## Server API Requirements

Your update server must provide these endpoints:

### 1. Update Check: `POST api/theme-update/`

**Request Body:**
```json
{
    "action": "get_version",
    "theme_slug": "my-theme",
    "current_version": "1.0.0",
    "site_url": "https://example.com",
    "wp_version": "6.6",
    "php_version": "8.1"
}
```

**Response (Update Available):**
```json
{
    "success": true,
    "version": "2.1.5",
    "current_version": "1.0.0", 
    "update_available": true,
    "requires_wp": "5.8",
    "tested_wp": "6.6",
    "requires_php": "7.4",
    "last_updated": "2025-07-29 19:22:29",
    "download_token": "abc123def456ghi789",
    "details_url": "https://your-server.com/theme-details/my-theme",
    "description": "A powerful and flexible WordPress theme with modern design.",
    "changelog": "### Version 2.1.5\n- Fixed responsive design issues\n- Added new customization options\n- Improved performance\n- Security updates",
    "installation": "1. Download theme zip\n2. Go to Appearance > Themes > Add New\n3. Upload and activate",
    "faq": "**Q: Compatible with page builders?**\nA: Yes, works with Elementor and Gutenberg.",
    "banners": {
        "high": "https://your-server.com/assets/banners/theme-banner-1544x500.jpg",
        "low": "https://your-server.com/assets/banners/theme-banner-772x250.jpg"
    },
    "icons": {
        "1x": "https://your-server.com/assets/icons/theme-icon-128x128.png",
        "2x": "https://your-server.com/assets/icons/theme-icon-256x256.png"
    },
    "screenshots": [
        {
            "src": "https://your-server.com/assets/screenshots/screenshot-1.jpg",
            "caption": "Homepage layout with hero section"
        }
    ],
    "file_size": "2.5 MB",
    "download_count": 1247,
    "rating": 4.8,
    "rating_count": 156
}
```

**Response (No Update):**
```json
{
    "success": true,
    "version": "1.0.0",
    "current_version": "1.0.0",
    "update_available": false,
    "message": "Your theme is up to date",
    "last_checked": "2025-07-29 19:22:29"
}
```

### 2. Download: `GET api/theme-download/`

**Query Parameters:**
- `theme_slug`: Theme directory name
- `download_token`: Token from update check response
- `site_url`: Requesting site URL

**Response:**
```json
{
    "success": true,
    "download_url": "https://your-server.com/downloads/my-theme-v2.1.5.zip?token=abc123&expires=1722283347",
    "file_name": "my-theme-v2.1.5.zip", 
    "file_size": "2621440",
    "expires_at": "2025-07-29 20:22:29",
    "version": "2.1.5",
    "checksum": "sha256:a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3"
}
```

### 3. Server Status: `GET api/status/`

**Response:**
```json
{
    "success": true,
    "status": "online",
    "server_time": "2025-07-29 19:22:29",
    "api_version": "1.0",
    "maintenance_mode": false,
    "supported_wp_version": "6.6",
    "supported_php_version": "7.4+"
}
```

### Error Response Format

All endpoints should return errors in this format:

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human readable error message",
        "details": "Additional error details (optional)"
    },
    "server_time": "2025-07-29 19:22:29"
}
```

**Common Error Codes:**
- `THEME_NOT_FOUND`: Theme slug not found
- `INVALID_TOKEN`: Download token invalid or expired
- `MISSING_THEME_SLUG`: Required theme_slug parameter missing
- `FILE_NOT_FOUND`: Theme file not found on server
- `TOKEN_EXPIRED`: Download token has expired

## Usage Examples

### Basic Implementation
```php
ThemeUpdater::create()
    ->setThemeSlug('my-awesome-theme')
    ->setVersion('1.2.3')
    ->setUpdateServer('https://updates.mysite.com/')
    ->init();
```

### Manual Update Check
```php
$updater = new ThemeUpdater();
$updater->setThemeSlug('my-theme')
       ->setVersion('1.0.0') 
       ->setUpdateServer('https://updates.example.com/');

$result = $updater->forceCheck();
if ($result && $result['update_available']) {
    echo "New version available: " . $result['version'];
}
```

### Check Server Status
```php
$updater = ThemeUpdater::create()
    ->setUpdateServer('https://updates.example.com/');

if ($updater->serverStatus()) {
    echo "Update server is online ✅";
} else {
    echo "Update server is offline ❌";
}
```

## Admin Interface

The updater automatically adds an admin page at **Appearance > Theme Updates** featuring:

- 📊 Current theme information display
- 🔄 Server status indicator
- 🔍 Manual update check button
- ⚡ Force update check option
- 📢 Update notifications and changelog

## Security Features

- **🔒 Token-based downloads**: Each download requires a unique, time-limited token
- **🌐 Site verification**: Downloads are tied to specific site URLs
- **⏰ Token expiration**: Download tokens automatically expire (default: 1 hour)
- **👤 Permission checks**: Only users with `update_themes` capability can access
- **🛡️ Nonce verification**: All AJAX requests use WordPress security nonces
- **📝 Request logging**: All update checks and downloads can be logged

## Performance & Caching

- ⚡ Update checks cached for 12 hours
- 🗑️ Cache automatically cleared after successful updates
- 🔄 Manual cache clearing via force check option
- 📦 Lightweight: Only loads in admin area
- 🚀 Non-blocking: Uses WordPress HTTP API with timeouts

## Server Implementation Example

```php
<?php
// api/theme-update/index.php
header('Content-Type: application/json');

$theme_slug = $_POST['theme_slug'] ?? '';
$current_version = $_POST['current_version'] ?? '';

// Your theme configuration
$themes = [
    'my-theme' => [
        'version' => '2.1.5',
        'requires_wp' => '5.8',
        'tested_wp' => '6.6',
        'file_path' => '/downloads/my-theme-v2.1.5.zip'
    ]
];

if (!isset($themes[$theme_slug])) {
    echo json_encode([
        'success' => false,
        'error' => ['code' => 'THEME_NOT_FOUND', 'message' => 'Theme not found']
    ]);
    exit;
}

$theme = $themes[$theme_slug];
$update_available = version_compare($current_version, $theme['version'], '<');

echo json_encode([
    'success' => true,
    'version' => $theme['version'],
    'current_version' => $current_version,
    'update_available' => $update_available,
    'download_token' => $update_available ? hash('sha256', $theme_slug . time()) : null,
    // ... other theme data
]);
```

## Requirements

- **WordPress:** 5.8 or higher
- **PHP:** 7.4 or higher
- **Server:** HTTP/HTTPS access to your update server
- **Permissions:** User must have `update_themes` capability
- **Network:** Outbound HTTP requests must be allowed

## File Structure

```
your-theme/
├── style.css              # Contains theme version
├── functions.php          # Initialize updater here
├── includes/
│   └── ThemeUpdater.php   # Main updater class
└── assets/               
    ├── banners/           # Theme banners (1544x500, 772x250)
    ├── icons/             # Theme icons (128x128, 256x256)  
    └── screenshots/       # Theme screenshots
```

## Troubleshooting

### ❌ Updates Not Showing
1. Check if update server is accessible
2. Verify API endpoints return correct JSON
3. Clear update cache: go to admin and click "Force Check"
4. Check WordPress error logs for API errors

### ❌ Download Failures
1. Ensure download tokens haven't expired
2. Check file permissions on server
3. Verify file paths in server configuration
4. Test download URL manually in browser

### ❌ Permission Errors
1. Confirm user has `update_themes` capability
2. Check WordPress nonce verification in AJAX calls
3. Verify admin area restrictions

### 🐛 Debug Mode

Enable WordPress debug logging:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs at: `/wp-content/debug.log`

## Best Practices

### For Theme Developers
- 📌 Always increment version number in `style.css`
- 📝 Maintain detailed changelog
- 🧪 Test updates on staging site first
- 🔄 Implement rollback mechanism if needed

### For Server Administrators
- 🛡️ Implement rate limiting (max 10 requests/minute per IP)
- 📊 Monitor server performance and API response times
- 💾 Use CDN for faster file downloads
- 🔄 Maintain backup servers for high availability
- 📈 Log all requests for analytics and debugging

---

**Author:** wichaksono  
**Package:** NeonWebId\DevTools\Utils\ThemeUpdater  
**Version:** 1.0.0  
**Last Updated:** 2025-07-29 19:22:29

## License

This project is open source. Feel free to modify and distribute according to your needs.