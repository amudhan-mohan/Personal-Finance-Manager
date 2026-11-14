<?php

declare(strict_types=1);

define('SITE_NAME', 'Personal Finance Manager');
define('SESSION_DURATION', 24 * 60 * 60); // 24 hours
define('APP_VERSION', '1.0.0');

/**
 * Returns a cache-busting build version query string based on the static asset's last build timestamp (filemtime).
 * Formatted as: YYYYMMDDHHMMSS (e.g. 20261114154454)
 */
function asset_version(string $assetRelativePath = 'assets/css/tailwind.css'): string
{
    $root = dirname(__DIR__);
    $filePath = $root . '/' . ltrim($assetRelativePath, '/');
    if (file_exists($filePath)) {
        return date('YmdHis', filemtime($filePath));
    }
    return date('Ymd');
}
