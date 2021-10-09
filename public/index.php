<?php

// Init the autoloader.
require __DIR__ . '/../vendor/autoload.php';

// Read settings.
$settings = require __DIR__ . '/../settings.php';

// Create the app.
$app = \PortierLdap\App::create($settings);

// Run the app.
$app->run();
