# API Reference

Complete API reference for WP Dependency Injection.

## Core Classes

### Application

Bootstrap class that initializes the Symfony DI container in WordPress.

#### Methods

##### `boot(): void` (static)

Boots the application and initializes the DI container.

```php
use Brianvarskonst\WordPress\DependencyInjection\Application;

Application::boot();
```

##### `getInstance(): Application` (static)

Returns the singleton instance.

```php
$app = Application::getInstance();
```

##### `getContainer(): ContainerInterface`

Returns the DI container instance.

```php
$container = Application::getInstance()->getContainer();
```

---

### Kernel

Builds and manages the Symfony DI container.

#### Constructor

```php
public function __construct(
    string $projectRoot,
    string $cacheDir,
    bool $debug = false,
    string $environment = 'prod'
)
```

**Parameters:**
- `$projectRoot` - WordPress root directory path
- `$cacheDir` - Container cache directory path
- `$debug` - Debug mode (true/false)
- `$environment` - Current environment (dev/stage/prod)

#### Methods

##### `getContainer(): ContainerInterface`

Returns the compiled container instance.

```php
$kernel = new Kernel($projectRoot, $cacheDir, false, 'prod');
$container = $kernel->getContainer();
```

##### `registerBundle(BundleInterface $bundle): void`

Registers a single bundle.

```php
$kernel->registerBundle(new YourPluginBundle());
```

##### `registerBundles(array $bundles): void`

Registers multiple bundles.

```php
$kernel->registerBundles([
    new Bundle1(),
    new Bundle2(),
]);
```

---

### Bundle Classes

#### BundleInterface

Interface that all bundles must implement.

```php
interface BundleInterface
{
    public function build(ContainerBuilder $container): void;
    public function boot(): void;
    public function getName(): string;
    public function getPath(): string;
}
```

#### AbstractBundle

Base class for creating bundles.

```php
abstract class AbstractBundle implements BundleInterface
{
    public function build(ContainerBuilder $container): void;
    public function boot(): void;
    public function getName(): string;
    public function getPath(): string;
    public function getContainerExtension(): ?ExtensionInterface;
    public function getNamespace(): string;
}
```

**Example:**

```php
use Brianvarskonst\WordPress\DependencyInjection\Bundle\AbstractBundle;

final class MyBundle extends AbstractBundle
{
    public function boot(): void
    {
        add_action('init', [$this, 'onInit']);
    }

    public function onInit(): void
    {
        // Initialization logic
    }
}
```

---

### BundleLoader

Discovers and loads bundles with their configurations.

#### Constructor

```php
public function __construct(string $projectRoot)
```

#### Methods

##### `registerBundle(BundleInterface $bundle): void`

Registers a bundle for loading.

```php
$loader = new BundleLoader($projectRoot);
$loader->registerBundle(new MyBundle());
```

##### `registerBundles(array $bundles): void`

Registers multiple bundles.

```php
$loader->registerBundles([
    new Bundle1(),
    new Bundle2(),
]);
```

##### `loadBundles(ContainerBuilder $container): void`

Loads all registered bundles into the container.

```php
$loader->loadBundles($containerBuilder);
```

##### `bootBundles(): void`

Boots all registered bundles.

```php
$loader->bootBundles();
```

##### `getBundles(): array`

Returns all registered bundles.

```php
$bundles = $loader->getBundles();
```

---

### BundlesConfigLoader

Reads and processes `config/bundles.php` for environment-aware bundle registration.

#### Constructor

```php
public function __construct(string $projectRoot, string $environment)
```

#### Methods

##### `loadBundles(): array`

Loads bundles from `config/bundles.php` filtered by environment.

```php
$loader = new BundlesConfigLoader($projectRoot, 'dev');
$bundles = $loader->loadBundles();
```

**Returns:** Array of instantiated bundle objects.

---

### TranslationLoader

Manages XLIFF translation resources for bundles.

#### Methods

##### `registerBundleTranslations(BundleInterface $bundle): void`

Registers translation paths for a bundle.

```php
$translationLoader = new TranslationLoader();
$translationLoader->registerBundleTranslations($bundle);
```

##### `getTranslationPaths(): array`

Returns all registered translation paths.

```php
$paths = $translationLoader->getTranslationPaths();
// Returns: ['BundleName' => '/path/to/translations', ...]
```

##### `loadTranslations(string $locale): array`

Loads translations for a specific locale.

```php
$translations = $translationLoader->loadTranslations('en');
// Returns: ['BundleName' => ['key' => 'translation', ...], ...]
```

---

## Helper Functions

### `sf_container(): ContainerInterface`

Returns the DI container instance.

```php
$container = sf_container();
```

**Returns:** `Symfony\Component\DependencyInjection\ContainerInterface`

---

### `sf_service(string $serviceId): ?object`

Retrieves a service from the container.

```php
$service = sf_service(MyService::class);

if ($service !== null) {
    $service->execute();
}
```

**Parameters:**
- `$serviceId` - Service class name or ID

**Returns:** Service instance or `null` if not found

---

## WordPress Hooks

### Actions

#### `symfony_container_ready`

Fired when the container is built and ready.

```php
add_action('symfony_container_ready', function() {
    $service = sf_service(MyService::class);
    $service->initialize();
});
```

#### `symfony_container_loaded`

Fired after container is loaded with the container as parameter.

```php
add_action('symfony_container_loaded', function($container) {
    // Container is available
}, 10, 1);
```

### Filters

#### `symfony_register_bundles`

Allows registering bundles programmatically.

```php
add_filter('symfony_register_bundles', function(array $bundles): array {
    $bundles[] = new MyBundle();
    return $bundles;
}, 10, 1);
```

**Parameters:**
- `$bundles` - Array of bundle instances

**Returns:** Modified array of bundles

---

## Container Methods

When accessing services via `sf_container()`, you have access to standard Symfony ContainerInterface methods:

### `get(string $id): object`

Retrieves a service by ID.

```php
$service = sf_container()->get(MyService::class);
```

**Throws:** `ServiceNotFoundException` if service not found

---

### `has(string $id): bool`

Checks if a service exists.

```php
if (sf_container()->has(MyService::class)) {
    $service = sf_container()->get(MyService::class);
}
```

**Returns:** `true` if service exists, `false` otherwise

---

### `getParameter(string $name): mixed`

Gets a container parameter.

```php
$apiKey = sf_container()->getParameter('my_plugin.api_key');
$debug = sf_container()->getParameter('kernel.debug');
```

**Throws:** `ParameterNotFoundException` if parameter doesn't exist

---

### `hasParameter(string $name): bool`

Checks if a parameter exists.

```php
if (sf_container()->hasParameter('my_plugin.api_key')) {
    $apiKey = sf_container()->getParameter('my_plugin.api_key');
}
```

**Returns:** `true` if parameter exists, `false` otherwise

---

## Configuration Classes

### Extension

Base class for container extensions.

```php
use Symfony\Component\DependencyInjection\Extension\Extension;

final class MyExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Load services and register parameters
    }

    public function getAlias(): string
    {
        return 'my_extension';
    }
}
```

---

### ConfigurationInterface

Interface for configuration tree definitions.

```php
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('my_config');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('api_key')->end()
                ->booleanNode('enabled')->end()
            ->end();

        return $treeBuilder;
    }
}
```

---

## Environment Variables

### Available Parameters

The following parameters are automatically registered:

- `kernel.project_dir` - WordPress root directory
- `kernel.cache_dir` - Container cache directory
- `kernel.debug` - Debug mode (bool)
- `kernel.environment` - Current environment (dev/stage/prod)

### Environment Variable Access

All environment variables are available as parameters with `env.` prefix:

```php
// From $_ENV['DATABASE_URL']
$dbUrl = $container->getParameter('env.database_url');

// From $_ENV['API_KEY']
$apiKey = $container->getParameter('env.api_key');
```

In YAML configuration:

```yaml
services:
    MyService:
        arguments:
            $apiKey: '%env.api_key%'
            $dbUrl: '%env.database_url%'
```

---

## Error Handling

### Common Exceptions

#### `ServiceNotFoundException`

Thrown when trying to get a non-existent service.

```php
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

try {
    $service = $container->get('non_existent_service');
} catch (ServiceNotFoundException $e) {
    error_log('Service not found: ' . $e->getMessage());
}
```

#### `ParameterNotFoundException`

Thrown when accessing a non-existent parameter.

```php
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

try {
    $param = $container->getParameter('non_existent_param');
} catch (ParameterNotFoundException $e) {
    error_log('Parameter not found: ' . $e->getMessage());
}
```

#### `InvalidArgumentException`

Thrown for invalid service definitions or configuration.

```php
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

try {
    // Invalid service configuration
} catch (InvalidArgumentException $e) {
    error_log('Invalid configuration: ' . $e->getMessage());
}
```

---

## Performance Optimization

### Container Caching

Containers are automatically cached per environment:

```
wp-content/uploads/symfony-cache/container_dev.php
wp-content/uploads/symfony-cache/container_stage.php
wp-content/uploads/symfony-cache/container_prod.php
```

### Clear Cache

```php
// Programmatically clear cache
$cacheDir = wp_upload_dir()['basedir'] . '/symfony-cache';
array_map('unlink', glob($cacheDir . '/*.php'));
```

### Lazy Services

Mark services as lazy to defer instantiation:

```yaml
services:
    MyExpensiveService:
        lazy: true
```

---

## Type Hints

For better IDE support, use proper type hints:

```php
use Symfony\Component\DependencyInjection\ContainerInterface;
use Brianvarskonst\WordPress\DependencyInjection\Bundle\BundleInterface;

/** @var ContainerInterface $container */
$container = sf_container();

/** @var MyService|null $service */
$service = sf_service(MyService::class);
```
