<?php

declare(strict_types=1);

namespace SaeedHosan\Module\Support\Contracts;

use SaeedHosan\Module\Support\ModuleCollection;

interface ModuleFinderContract
{
    /**
     * Find a module by name.
     */
    public function find(string $name): ?ModuleContract;

    /**
     * Get all resolved modules.
     */
    public function loaded(): ModuleCollection;

    /**
     * Get all resolved modules (alias of loaded).
     */
    public function all(): ModuleCollection;
}
