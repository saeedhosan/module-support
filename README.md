# Module Support for Laravel

Your friendly companion for building modular Laravel applications. This package makes it dead simple to organize your Laravel app into self-contained modules with their own structure.

## What is this?

Think of modules as building blocks for your Laravel app. Instead of dumping everything into `app/Http/Controllers`, `app/Models`, and friends, you can group related functionality into modules. Each module can have its own controllers, models, views, migrations—everything it needs to work independently.

This package gives you the tools to discover, manage, and work with those modules effortlessly.

## Requirements

- **PHP**: 8.2 or newer
- **Laravel**: 11, 12, or 13

## Installation

Install via Composer:

```bash
composer require saeedhosan/module-support
```

The service provider registers itself automatically, so you're good to go—no extra setup needed.

## Configuration

Want to customize how modules are organized? Publish the config file:

```bash
php artisan vendor:publish --tag=module-config
```

Here's what you can tweak in `config/module.php`:

```php
[
    'directory' => 'modules',      // Where your modules live
    'namespace' => 'Modules',       // The root namespace for modules
    'lowercase' => true,            // Normalize module names to lowercase
    'vendor' => 'modules',          // Vendor directory for module packages
    'view_path' => 'resources/views',
    'app_path' => 'app',
]
```

## Quick Start

### Module Structure

A typical module looks like this:

```
modules/
└── Blog/
    ├── app/
    │   ├── Http/
    │   │   └── Controllers/
    │   └── Models/
    ├── resources/
    │   └── views/
    ├── routes/
    ├── composer.json
    └── ...
```

Each module has its own `composer.json` that defines its namespace and autoloading—just like a regular package.

### Working with Modules

**Get a specific module:**

```php
use SaeedHosan\Module\Support\ModuleManager;

$modules = app(ModuleManager::class);

$blog = $modules->module('blog');

// Or use the helper
$blog = module('blog');

$blog->name();        // "Blog"
$blog->path();        // "/path/to/modules/Blog"
$blog->namespace();  // "Modules\Blog"
$blog->version();    // "1.0.0"
$blog->exists();     // true/false
$blog->active();     // true/false (checks if autoloaded & discovered)
```

**Loop through all modules:**

```php
foreach (module()->all() as $module) {
    echo $module->name();
    echo $module->path();
}
```

**Check if a module exists:**

```php
if (module()->find('blog')) {
    // Blog module is here and ready to go
}
```

**Get module info from its composer.json:**

```php
$module = module('blog');

$module->composer();      // Full composer.json contents
$module->providers();     // ['Modules\Blog\Providers\BlogServiceProvider']
$module->description();   // "A simple blogging module"
```

### Helpers

We've got a couple of handy helpers up our sleeves:

```php
// Get the module manager
module();

// Get a specific module
module('blog');

// Get all modules
module()->all();

// Check if a module exists
module()->find('blog');
```

### Blade Components

This package includes a `<x-module>` Blade component for conditionally rendering content based on module status:

```blade
{{-- Show content only if the Blog module is active --}}
<x-module name="blog">
    <div class="blog-content">
        <h1>Welcome to our Blog!</h1>
    </div>
</x-module>
```

The component accepts these attributes:
- `name` (required): The module name to check
- `title` (optional): Fallback title for the error message

If the module is not active, it displays a placeholder message. You can also customize the wrapper styles using standard HTML attributes:

```blade
<x-module name="blog" class="custom-wrapper" id="blog-section">
    <p>Blog content here</p>
</x-module>
```

### Blade Directives

The package provides a `@module` directive for conditionally including content:

```blade
@module('blog')
    <p>Blog module is active and ready!</p>
@endmodule

@module('payments')
    <p>Payment features available</p>
@endmodule
```

### Bootstrapping Module Providers

If your module has service providers defined in its `composer.json`, you can register them manually:

```php
$module = module('blog');
$module->register();
```

Or use the manager:

```php
app(ModuleManager::class)->registerProviders([
    'Modules\Blog\Providers\BlogServiceProvider',
]);
```

## Testing

The package comes with a solid test suite. Make sure everything still works:

```bash
composer test
```

## Contributing

Found a bug or want to add a feature? Pull requests are totally welcome. Just make sure the tests pass before submitting.

---

**Module support** was created by **[Saeed Hosan](https://www.linkedin.com/in/saeedhosan)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
