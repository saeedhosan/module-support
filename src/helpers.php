<?php

declare(strict_types=1);

use SaeedHosan\Module\Support\ModuleManager;
use SaeedHosan\Module\Support\Module;

if (! function_exists('module')) {
    /**
     * Get the module manager or a module instance.
     */
    function module(?string $name = null): ModuleManager|Module
    {
        /** @var ModuleManager $manager */
        $manager = app(ModuleManager::class);

        if ($name === null) {
            return $manager;
        }

        return $manager->module($name);
    }
}
