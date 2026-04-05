<?php

declare(strict_types=1);

return [
    'lowercase' => true,       // whether the module directory always fore to lowercase e.g Auth to auth
    'directory' => 'modules',
    'namespace' => 'Modules',  // module root namespace
    'vendor' => 'modules',  // composer vendor
    'view_path' => 'resources/views',  // composer vendor
    'app_path' => 'app',      // application soruce directory inside module
];
