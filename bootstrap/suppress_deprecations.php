<?php

/**
 * Suppress PHP 8.4 deprecation warnings in development
 * This file helps clean up the console output during development
 */

// Only suppress deprecation warnings in development environment
if (env('APP_ENV') === 'local' || env('APP_ENV') === 'development') {
    // Set error reporting to exclude deprecation warnings
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    
    // Set a custom error handler for deprecation warnings
    set_error_handler(function ($severity, $message, $file, $line) {
        // Skip deprecation warnings
        if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
            return true; // Don't execute PHP internal error handler
        }
        
        // Let other errors be handled normally
        return false;
    }, E_DEPRECATED | E_USER_DEPRECATED);
}
