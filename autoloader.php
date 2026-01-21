<?php

/**
 * autoloader.php
 * PSR-4 Autoloader for the App namespace
 */

spl_autoload_register(function ($class) {
    // Define the base namespace
    $prefix = 'App\\';

    // Check if the class uses the App namespace
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    // Get the relative class name
    $relativeClass = substr($class, strlen($prefix));

    // Build the file path
    $baseDir = __DIR__ . '/src/';
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // If the file exists, include it
    if (file_exists($file)) {
        require $file;
    }
});
