<?php

// Init the autoloader.
require __DIR__ . '/../vendor/autoload.php';

// Read settings.
$settings = require __DIR__ . '/../settings.php';

// Set a default cache directory.
if (empty($settings['cacheDir'])) {
    $settings['cacheDir'] = __DIR__ . '/../_cache';
}

// Create the app.
$app = \PortierLdap\App::create($settings);

// Run the app.
$app->run();
