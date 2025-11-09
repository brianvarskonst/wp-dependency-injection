# Installation Guide

Complete installation guide for WP Dependency Injection library.

## System Requirements

- **PHP**: 8.2 or higher
- **WordPress**: 6.0 or higher
- **Composer**: Latest stable version
- **Server**: Apache/Nginx with mod_rewrite enabled

## Installation Steps

### Step 1: Install via Composer

Add the package to your WordPress project:

```bash
composer require brianvarskonst/wp-dependency-injection
```

Or add to your `composer.json`:

```json
{
    "require": {
        "brianvarskonst/wp-dependency-injection": "^1.0"
    }
}
```

Then run:

```bash
composer install
```

### Step 2: Create Must-Use Plugin

Must-Use (MU) plugins are automatically loaded by WordPress before regular plugins, ensuring the DI container is available early in the WordPress bootstrap process.

Create the file `wp-content/mu-plugins/application-starter.php`:

```php
<?php

declare(strict_types=1);

/**
 * Plugin Name: Application Starter
 * Description: Integrates Symfony DI Container into WordPress
 * Version: 1.0.0
 * Requires PHP: 8.2
 */

namespace YourNamespace\AppStarter;

use Brianvarskonst\WordPress\DependencyInjection\Application;

if (!defined('ABSPATH')) {
    exit;
}

// Adjust path based on your vendor location
require __DIR__ . '/../../vendor/autoload.php';

Application::boot();
```

**Important**: Adjust the autoloader path based on your project structure:
- Standard WordPress: `__DIR__ . '/../../vendor/autoload.php'`
- WPStarter/Bedrock: `__DIR__ . '/../../../vendor/autoload.php'`

### Step 3: Create Configuration Directory

Create the configuration directory structure in your WordPress root:

```bash
mkdir -p config/packages/{dev,stage,prod}
```

### Step 4: Create bundles.php

Create `config/bundles.php`:

```php
<?php

declare(strict_types=1);

return [
    // Your bundles will be registered here
];
```

### Step 5: Create services.yaml

Create `config/services.yaml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'
```

### Step 6: Set Environment Variable

If using WPStarter, add to your `.env` file:

```env
WORDPRESS_ENV=dev
```

Available values: `dev`, `stage`, `prod`

If not using WPStarter, you can define the environment in `wp-config.php`:

```php
define('WP_ENVIRONMENT_TYPE', 'development'); // or 'staging', 'production'
```

### Step 7: Verify Installation

Add this to a plugin or theme to verify:

```php
add_action('init', function() {
    $container = sf_container();

    if ($container instanceof \Symfony\Component\DependencyInjection\ContainerInterface) {
        echo 'DI Container successfully loaded!';
    }
}, 999);
```

## Directory Structure

After installation, your project should look like this:

```
wordpress-root/
├── .env                              # Environment configuration (WPStarter)
├── config/
│   ├── bundles.php                  # Bundle registration
│   ├── services.yaml                # Global service configuration
│   └── packages/
│       ├── dev/
│       │   └── services.yaml        # Dev-specific overrides
│       ├── stage/
│       │   └── services.yaml        # Stage-specific overrides
│       └── prod/
│           └── services.yaml        # Prod-specific overrides
├── src/                             # Your application code
├── vendor/                          # Composer dependencies
├── composer.json
├── composer.lock
└── wp-content/
    ├── uploads/
    │   └── symfony-cache/          # Container cache (auto-generated)
    ├── plugins/
    └── mu-plugins/
        └── application-starter.php  # MU plugin bootstrap
```

## WPStarter Integration

If you're using [WPStarter](https://github.com/wecodemore/wpstarter), the library integrates automatically:

1. WPStarter loads `.env` files
2. WPStarter sets environment variables
3. Application reads `WORDPRESS_ENV` from environment
4. Container is built with correct environment configuration

### WPStarter composer.json Example

```json
{
    "require": {
        "wecodemore/wpstarter": "^3.0",
        "brianvarskonst/wp-dependency-injection": "^1.0"
    },
    "extra": {
        "wpstarter": {
            "env-file": ".env",
            "env-dir": "."
        }
    }
}
```

## Troubleshooting

### Issue: Class not found errors

**Solution**: Ensure Composer autoloader is correctly loaded:

```bash
composer dump-autoload -o
```

### Issue: Container not available

**Solution**: Check MU plugin is loaded:

```php
// Add to wp-config.php temporarily
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check debug.log for errors
```

### Issue: Services not found

**Solution**: Clear container cache:

```bash
rm -rf wp-content/uploads/symfony-cache/
```

### Issue: Permission denied on cache directory

**Solution**: Set correct permissions:

```bash
chmod 755 wp-content/uploads/
mkdir -p wp-content/uploads/symfony-cache
chmod 755 wp-content/uploads/symfony-cache
```

### Issue: Wrong environment loaded

**Solution**: Verify environment variable:

```php
// Add to plugin to debug
add_action('init', function() {
    error_log('WORDPRESS_ENV: ' . ($_ENV['WORDPRESS_ENV'] ?? 'not set'));
    error_log('WP_ENVIRONMENT_TYPE: ' . (defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'not set'));
});
```

## Next Steps

- [Create Your First Bundle](bundles.md)
- [Configure Services](services.md)
- [Environment Configuration](environments.md)
- [Best Practices](best-practices.md)

## Uninstallation

To remove the library:

1. Remove from composer:
```bash
composer remove brianvarskonst/wp-dependency-injection
```

2. Delete MU plugin:
```bash
rm wp-content/mu-plugins/application-starter.php
```

3. Delete configuration:
```bash
rm -rf config/
```

4. Delete cache:
```bash
rm -rf wp-content/uploads/symfony-cache/
```
