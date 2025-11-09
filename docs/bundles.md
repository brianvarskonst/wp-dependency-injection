# Bundle System

Learn how to create and manage Symfony-style bundles in WordPress.

## What is a Bundle?

A bundle is a self-contained package that encapsulates:
- Services and their configuration
- Container extensions
- Translation resources
- Configuration definitions

Bundles enable modular, reusable, and maintainable WordPress plugins using Symfony's proven architecture.

## Creating a Bundle

### Step 1: Bundle Class

Create your bundle class extending `AbstractBundle`:

```php
<?php

declare(strict_types=1);

namespace YourPlugin;

use Brianvarskonst\WordPress\DependencyInjection\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

final class YourPluginBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register compiler passes if needed
        // $container->addCompilerPass(new YourCompilerPass());
    }

    public function boot(): void
    {
        // Bootstrap your bundle
        add_action('init', [$this, 'onInit']);
        add_filter('the_content', [$this, 'modifyContent']);
    }

    public function onInit(): void
    {
        // Initialization logic
    }

    public function modifyContent(string $content): string
    {
        // Your filter logic
        return $content;
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DependencyInjection\YourPluginExtension();
    }
}
```

### Step 2: Directory Structure

Organize your plugin following Symfony conventions:

```
plugins/your-plugin/
├── your-plugin.php               # Main plugin file (WordPress header + Bundle class)
├── composer.json                 # Plugin dependencies (optional)
├── DependencyInjection/
│   ├── YourPluginExtension.php  # Container extension
│   └── Configuration.php         # Configuration tree
├── Resources/
│   ├── config/
│   │   └── services.yaml        # Service definitions
│   └── translations/
│       ├── messages.en.xliff
│       └── messages.de.xliff
├── Service/
│   ├── EmailService.php
│   ├── ApiService.php
│   └── CacheService.php
├── Controller/
│   └── ApiController.php
└── Entity/
    └── User.php
```

### Step 3: Container Extension

Create `DependencyInjection/YourPluginExtension.php`:

```php
<?php

declare(strict_types=1);

namespace YourPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class YourPluginExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->loadServices($container);
        $this->registerParameters($container, $config);
    }

    public function getAlias(): string
    {
        return 'your_plugin';
    }

    private function loadServices(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');
    }

    private function registerParameters(ContainerBuilder $container, array $config): void
    {
        foreach ($config as $key => $value) {
            $container->setParameter('your_plugin.' . $key, $value);
        }
    }
}
```

### Step 4: Configuration Definition

Create `DependencyInjection/Configuration.php`:

```php
<?php

declare(strict_types=1);

namespace YourPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('your_plugin');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('api_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('API key for external service')
                ->end()
                ->booleanNode('debug_mode')
                    ->defaultFalse()
                    ->info('Enable debug mode')
                ->end()
                ->integerNode('cache_ttl')
                    ->defaultValue(3600)
                    ->min(0)
                    ->info('Cache time-to-live in seconds')
                ->end()
                ->arrayNode('features')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                    ->info('Enabled features')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
```

## Registering Bundles

### Method 1: config/bundles.php (Recommended)

Edit `config/bundles.php` in your WordPress root:

```php
<?php

return [
    // Load in all environments
    YourPlugin\YourPluginBundle::class => ['all' => true],

    // Development only
    YourPlugin\DebugBundle::class => ['dev' => true],

    // Multiple environments
    YourPlugin\ProfilerBundle::class => ['dev' => true, 'stage' => true],

    // Production only
    YourPlugin\CacheBundle::class => ['prod' => true],
];
```

### Method 2: WordPress Filter (Fallback)

Add to your plugin file:

```php
add_filter('symfony_register_bundles', function(array $bundles): array {
    $bundles[] = new YourPlugin\YourPluginBundle();
    return $bundles;
}, 10, 1);
```

## Bundle Services Configuration

Create `Resources/config/services.yaml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    # Auto-register services
    YourPlugin\Service\:
        resource: '../../Service/*'
        exclude: '../../Service/{Tests,DependencyInjection}'

    # Public services (accessible via sf_service())
    YourPlugin\Service\EmailService:
        public: true

    # Service with explicit configuration
    YourPlugin\Service\ApiService:
        public: true
        arguments:
            $apiKey: '%your_plugin.api_key%'
            $debugMode: '%your_plugin.debug_mode%'
            $cacheService: '@YourPlugin\Service\CacheService'

    # Controller
    YourPlugin\Controller\ApiController:
        public: true
        tags: ['controller.service_arguments']
```

## Root Configuration

Create `config/packages/your_plugin.yaml` in WordPress root:

```yaml
your_plugin:
    api_key: '%env(YOUR_PLUGIN_API_KEY)%'
    debug_mode: '%kernel.debug%'
    cache_ttl: 7200
    features:
        - email_notifications
        - api_integration
        - caching
```

### Environment-Specific Configuration

Override settings per environment:

**config/packages/dev/your_plugin.yaml:**
```yaml
your_plugin:
    debug_mode: true
    cache_ttl: 60
```

**config/packages/prod/your_plugin.yaml:**
```yaml
your_plugin:
    debug_mode: false
    cache_ttl: 86400
```

## Bundle Lifecycle

### 1. Registration
Bundles are registered via `config/bundles.php` or WordPress filter.

### 2. Build Phase
`build()` method is called during container compilation:

```php
public function build(ContainerBuilder $container): void
{
    parent::build($container);

    // Register compiler passes
    $container->addCompilerPass(new CustomCompilerPass());
}
```

### 3. Boot Phase
`boot()` method is called after container is ready:

```php
public function boot(): void
{
    // Register WordPress hooks
    add_action('init', [$this, 'onInit']);

    // Access services
    $service = sf_service(Service\MyService::class);
}
```

## Cross-Bundle Dependencies

Bundles can depend on services from other bundles:

```php
namespace PluginB\Service;

use PluginA\Service\EmailService;

final class NotificationService
{
    public function __construct(
        private readonly EmailService $emailService
    ) {
    }

    public function notify(string $message): void
    {
        $this->emailService->send('admin@example.com', $message);
    }
}
```

Configuration:

```yaml
# PluginA services
services:
    PluginA\Service\EmailService:
        public: true

# PluginB services
services:
    PluginB\Service\NotificationService:
        arguments:
            $emailService: '@PluginA\Service\EmailService'
```

## Bundle Best Practices

### 1. Naming Convention
- Bundle class: `{Name}Bundle` (e.g., `EmailBundle`, `ApiBundle`)
- Extension alias: lowercase with underscores (e.g., `email`, `api`)
- Namespace: Bundle name without "Bundle" suffix

### 2. Service Visibility
- Keep services `public: false` by default
- Only make services `public: true` if accessed via `sf_service()`
- Use dependency injection over service location

### 3. Configuration
- Define clear configuration structure in `Configuration.php`
- Use environment variables for sensitive data
- Provide sensible defaults
- Add validation rules

### 4. WordPress Integration
- Register hooks in `boot()` method
- Use early return pattern
- Avoid global state
- Follow WordPress coding standards for hooks

### 5. Testing
- Keep bundle logic testable
- Avoid WordPress dependencies in business logic
- Use dependency injection for testability

## Example: Complete Email Bundle

**EmailBundle.php:**
```php
<?php

namespace App\Email;

use Brianvarskonst\WordPress\DependencyInjection\Bundle\AbstractBundle;

final class EmailBundle extends AbstractBundle
{
    public function boot(): void
    {
        add_action('user_register', [$this, 'sendWelcomeEmail']);
    }

    public function sendWelcomeEmail(int $userId): void
    {
        $service = sf_service(Service\EmailService::class);
        $service->sendWelcome($userId);
    }

    public function getContainerExtension(): ?\Symfony\Component\DependencyInjection\Extension\ExtensionInterface
    {
        return new DependencyInjection\EmailExtension();
    }
}
```

**Service/EmailService.php:**
```php
<?php

namespace App\Email\Service;

final class EmailService
{
    public function __construct(
        private readonly string $fromEmail,
        private readonly MailerService $mailer
    ) {
    }

    public function sendWelcome(int $userId): void
    {
        $user = get_userdata($userId);

        $this->mailer->send(
            $user->user_email,
            'Welcome!',
            'Welcome to our site!'
        );
    }
}
```

**Resources/config/services.yaml:**
```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    App\Email\Service\:
        resource: '../../Service/*'

    App\Email\Service\EmailService:
        public: true
        arguments:
            $fromEmail: '%email.from_address%'
```

**config/packages/email.yaml:**
```yaml
email:
    from_address: '%env(EMAIL_FROM_ADDRESS)%'
    smtp_host: '%env(SMTP_HOST)%'
    smtp_port: '%env(SMTP_PORT)%'
```

## Next Steps

- [Service Configuration](services.md)
- [Translation Resources](translations.md)
- [Environment Management](environments.md)
- [Best Practices](best-practices.md)
