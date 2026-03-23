<?php

declare(strict_types=1);

namespace SaeedHosan\Module\Support;

use Illuminate\Support\Collection;

/**
 * @extends Collection<int, Module>
 */
class ModuleCollection extends Collection
{
    /**
     * Get the module names for the collection.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return $this->map(static fn (Module $module): string => $module->name())->values()->all();
    }
}
