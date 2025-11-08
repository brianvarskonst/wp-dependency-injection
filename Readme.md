# WordPress Dependency Injection

Symfony DI Container integration for WordPress

## Directory Layout

```
wp-content/plugins/example-plugin/
├── example-plugin.php                 # Main plugin file with Bundle class
├── DependencyInjection/
│   ├── ExamplePluginExtension.php    # Container extension
│   └── Configuration.php              # Configuration definition
├── Resources/
│   ├── config/
│   │   └── services.yaml             # Bundle services
│   └── translations/
│       ├── messages.en.xliff
│       └── messages.de.xliff
├── Service/
│   ├── PluginService.php
│   └── AnotherService.php
├── Controller/
│   └── ApiController.php
└── composer.json
```

## Root Configuration

```
wordpress-root/config/packages/
├── example_plugin.yaml               # Plugin configuration
├── another_plugin.yaml
└── dev/
    ├── example_plugin.yaml           # Dev overrides
    └── another_plugin.yaml
```

## Key Features

### 1. Bundle Registration
Plugins register as bundles via WordPress filter:
```php
add_filter('symfony_register_bundles', function($bundles) {
    $bundles[] = new ExamplePluginBundle();
    return $bundles;
});
```

### 2. Dependency Injection Across Bundles
Services from one plugin/bundle can be injected into another:
```yaml
# Plugin A services.yaml
App\PluginA\Service\EmailService:
    public: true

# Plugin B services.yaml
App\PluginB\Service\NotificationService:
    arguments:
        $emailService: '@App\PluginA\Service\EmailService'
```

### 3. Configuration from Root
Configure plugins from root `config/packages/`:
```yaml
# config/packages/example_plugin.yaml
example_plugin:
    api_key: '%env(API_KEY)%'
    debug_mode: false
```

### 4. Translation Resources
Bundles can provide translations:
```
Resources/translations/messages.en.xliff
Resources/translations/messages.de.xliff
```

### 5. Autowiring Support
Services automatically resolve dependencies:
```php
class PluginService {
    public function __construct(
        private LoggerService $logger,
        private EmailService $emailService
    ) {}
}
```

## Usage Example

### In Plugin Code
```php
// Get service from container
$service = sf_service(ExamplePlugin\Service\PluginService::class);
$service->execute();

// Access via container
$container = sf_container();
$service = $container->get('example_plugin.service');
```

### Cross-Bundle Dependencies
```php
namespace PluginB\Service;

use PluginA\Service\SharedService;

class MyService {
    public function __construct(
        private SharedService $sharedService
    ) {}
}
```

## Configuration Validation

Bundle configuration is validated via Configuration class:
```php
final class Configuration implements ConfigurationInterface {
    public function getConfigTreeBuilder(): TreeBuilder {
        // Define valid config structure
    }
}
```

## Copyright (c) 2025 Brianvarskonst
This software is only to be used for the specific project for which it was transmitted.! Since it's released under the GIL License you arent allowed to publish or for commercial useage.
