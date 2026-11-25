<?php

/**
 * Router for PHP's built-in server during the Playwright run.
 *
 * It does what Laravel's own server.php and public/index.php do, with one
 * difference: a `public/hot` file left by a running Vite dev server is
 * ignored, so the pages always load the production build in `public/build`.
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;

$basePath = dirname(__DIR__, 2);
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && is_file($basePath.'/public'.$uri)) {
    return false;
}

define('LARAVEL_START', microtime(true));

require $basePath.'/vendor/autoload.php';

/** @var Application $app */
$app = require $basePath.'/bootstrap/app.php';

$app->afterResolving(Vite::class, function (Vite $vite) use ($basePath): void {
    $vite->useHotFile($basePath.'/storage/framework/e2e-no-hot-file');
});

$app->handleRequest(Request::capture());
