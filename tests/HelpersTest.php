<?php

declare(strict_types=1);

use SaeedHosan\Module\Support\Module;
use SaeedHosan\Module\Support\ModuleCollection;
use SaeedHosan\Module\Support\ModuleManager;
use Tests\TestCase;

uses(TestCase::class);

it('returns module manager when called without arguments', function (): void {
    expect(module())->toBeInstanceOf(ModuleManager::class);
});

it('returns module instance when called with module name', function (): void {
    expect(module('Blog'))->toBeInstanceOf(Module::class);
});

it('returns the same module manager instance on multiple calls', function (): void {
    $first = module();
    $second = module();

    expect($first)->toBe($second);
});

it('returns the same module instance for the same module name', function (): void {
    $first = module('Blog');
    $second = module('Blog');

    expect($first)->toBe($second);
});

it('handles module name with different cases', function (): void {
    expect(module('blog'))->toBeInstanceOf(Module::class);
    expect(module('BLOG'))->toBeInstanceOf(Module::class);
    expect(module('Blog'))->toBeInstanceOf(Module::class);
});

it('returns module with correct name via manager', function (): void {
    $module = module('Blog');

    expect($module->name())->toBe('blog');
});

it('can access loaded() method through helper', function (): void {
    expect(module()->loaded())->toBeInstanceOf(ModuleCollection::class);
});

it('can access all() method through helper', function (): void {
    expect(module()->all())->toBeInstanceOf(ModuleCollection::class);
});

it('can access find() method through helper', function (): void {
    expect(module()->find('NonExistent'))->toBeNull();
});
