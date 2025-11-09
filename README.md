# WordPress Dependency Injection

A professional Symfony Dependency Injection Container integration for WordPress, bringing enterprise-level dependency management and the Symfony Bundle System to WordPress applications.

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.0%2B-black)](https://symfony.com/)
[![License](https://img.shields.io/badge/license-%20%20GNU%20GPLv3%20-green)](LICENSE)

## Features

* ✅ **Full Symfony DI Container** - Complete container implementation with autowiring and autoconfiguration
* ✅ **Bundle System** - Symfony-style bundle architecture for modular plugin development
* ✅ **Environment-Aware** - Automatic environment detection (dev/stage/prod) with separate container caching
* ✅ **Cross-Bundle DI** - Share services between plugins seamlessly
* ✅ **Root Configuration** - Centralized configuration in `config/` directory
* ✅ **Translation Support** - XLIFF translation resources per bundle
* ✅ **WPStarter Compatible** - Works out-of-the-box with WPStarter for environment management
* ✅ **Enterprise Standards** - KISS, DRY, SOLID principles with PSR-4 autoloading

## Requirements

- PHP 8.2 or higher
- WordPress 6.0+
- Composer
- Symfony DI Components 7.0+

## Installation

### 1. Install via Composer

```bash
composer require brianvarskonst/wp-dependency-injection
```

### 2. Create Must-Use Plugin

Create `wp-content/mu-plugins/application-starter.php`:

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

require __DIR__ . '/../../vendor/autoload.php';

Application::boot();
```

### 3. Create Configuration Structure

```bash
mkdir -p config/packages/{dev,stage,prod}
touch config/bundles.php
touch config/services.yaml
```

### 4. Set Environment Variable

In your `.env` file (handled by WPStarter):

```env
WORDPRESS_ENV=dev
```

## Quick Start

### Define Your Bundles

Create `config/bundles.php`:

```php
<?php

return [
    App\CoreBundle::class => ['all' => true],
    YourPlugin\PluginBundle::class => ['all' => true],
    App\DebugBundle::class => ['dev' => true],
    App\CacheBundle::class => ['prod' => true],
];
```

### Configure Services

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
```

### Create a Bundle

```php
<?php

namespace YourPlugin;

use Brianvarskonst\WordPress\DependencyInjection\Bundle\AbstractBundle;

final class PluginBundle extends AbstractBundle
{
    public function boot(): void
    {
        add_action('init', [$this, 'onInit']);
    }

    public function onInit(): void
    {
        // Your plugin initialization
    }
}
```

### Access Services

```php
// Get container
$container = sf_container();

// Get service
$service = sf_service(YourPlugin\Service\MyService::class);

// Or use in WordPress hooks
add_action('init', function() {
    $service = sf_service(YourPlugin\Service\MyService::class);
    $service->execute();
});
```

## Documentation

- [Installation Guide](docs/installation.md)
- [Bundle System](docs/bundles.md)
- [Configuration](docs/configuration.md)
- [Service Registration](docs/services.md)
- [Environment Management](docs/environments.md)
- [Translation Resources](docs/translations.md)
- [Best Practices](docs/best-practices.md)
- [API Reference](docs/api-reference.md)

## Architecture

### Core Components

- **Application** - Bootstrap class that initializes the DI container
- **Kernel** - Builds and caches the Symfony container
- **BundleLoader** - Discovers and loads bundles with environment awareness
- **BundlesConfigLoader** - Reads `config/bundles.php` for bundle registration
- **TranslationLoader** - Manages XLIFF translation resources

### Directory Structure

```
wordpress-root/
├── config/
│   ├── bundles.php              # Bundle registration
│   ├── services.yaml            # Global services
│   └── packages/
│       ├── your_bundle.yaml     # Bundle-specific config
│       ├── dev/
│       ├── stage/
│       └── prod/
├── src/
│   └── Integration/
│       └── Bundle/
└── wp-content/
    ├── plugins/
    │   └── your-plugin/
    │       ├── YourPluginBundle.php
    │       ├── DependencyInjection/
    │       ├── Resources/
    │       │   ├── config/
    │       │   │   └── services.yaml
    │       │   └── translations/
    │       └── Service/
    └── mu-plugins/
        └── application-starter.php
```

## Environment Detection

The library detects environments in the following order:

1. `$_ENV['WORDPRESS_ENV']` or `$_SERVER['WORDPRESS_ENV']` (WPStarter)
2. `WP_ENVIRONMENT_TYPE` constant
3. `WP_DEBUG` constant (fallback: dev if true, prod if false)

Supported environments: `dev`, `stage`, `prod`

## Bundle Configuration

### config/bundles.php

```php
<?php

return [
    // Load in all environments
    App\CoreBundle::class => ['all' => true],

    // Development only
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true],

    // Multiple environments
    App\ProfilerBundle::class => ['dev' => true, 'stage' => true],

    // Production only
    App\CacheBundle::class => ['prod' => true],
];
```

### Plugin Bundle Structure

```
plugins/your-plugin/
├── YourPluginBundle.php          # Bundle class
├── DependencyInjection/
│   ├── YourPluginExtension.php   # Container extension
│   └── Configuration.php         # Config definition
├── Resources/
│   ├── config/
│   │   └── services.yaml         # Bundle services
│   └── translations/
│       └── messages.en.xliff
└── Service/
    └── YourService.php
```

## Service Configuration

### Bundle services.yaml

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    YourPlugin\Service\:
        resource: '../../Service/*'
        exclude: '../../Service/{Tests}'

    YourPlugin\Service\PublicService:
        public: true
        arguments:
            $apiKey: '%your_plugin.api_key%'
```

### Root configuration

Create `config/packages/your_plugin.yaml`:

```yaml
your_plugin:
    api_key: '%env(YOUR_PLUGIN_API_KEY)%'
    debug_mode: false
    features:
        - feature_one
        - feature_two
```

## Cross-Bundle Dependencies

Services from different bundles can depend on each other:

```php
namespace PluginB\Service;

use PluginA\Service\SharedService;

final class NotificationService
{
    public function __construct(
        private readonly SharedService $sharedService
    ) {
    }
}
```

## Helper Functions

```php
// Get container instance
$container = sf_container();

// Get service (returns null if not found)
$service = sf_service(MyService::class);

// Check if service exists
if ($container->has(MyService::class)) {
    $service = $container->get(MyService::class);
}
```

## WordPress Integration

### Action Hook

```php
add_action('symfony_container_ready', function() {
    // Container is built and ready
    $service = sf_service(MyService::class);
});
```

### Service Usage in WordPress

```php
add_action('init', function() {
    $service = sf_service(YourPlugin\Service\EmailService::class);
    $service->sendWelcomeEmail();
});

add_shortcode('my_shortcode', function($atts) {
    $service = sf_service(YourPlugin\Service\ShortcodeService::class);
    return $service->render($atts);
});
```

## Performance

- **Container Caching** - Compiled containers cached per environment
- **Lazy Loading** - Services instantiated only when needed
- **Optimized Autoloader** - Composer's optimized autoloader enabled
- **Production Mode** - Debug features disabled in production

Cache location: `wp-content/uploads/symfony-cache/container_{environment}.php`

Clear cache:
```bash
rm -rf wp-content/uploads/symfony-cache/
```

## Security

- No browser storage APIs used (localStorage/sessionStorage not supported)
- Early returns pattern for security checks
- Strict type declarations throughout
- Input validation and sanitization
- Protected environment variable handling

## Coding Standards

This library follows:
- **Enterprise-level** code quality
- **KISS** (Keep It Simple, Stupid)
- **DRY** (Don't Repeat Yourself)
- **SOLID** principles
- **PSR-4** autoloading
- **PSR-12** coding style
- **SoC** (Separation of Concerns)

## Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Testing

```bash
composer test
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/brianvarskonst/wp-dependency-injection/issues)
- **Documentation**: [Full Documentation](https://github.com/brianvarskonst/wp-dependency-injection/wiki)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## Credits

Built with ❤️ by [Brianvarskonst](https://github.com/brianvarskonst)

Powered by [Symfony Components](https://symfony.com/components)

## Copyright (c) 2025 Brianvarskonst
This software is only to be used for the specific project for which it was transmitted.! Since it's released under the GIL License you arent allowed to publish or for commercial useage.
