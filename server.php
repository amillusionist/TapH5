<?php

$publicPath = __DIR__.'/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Only serve real static files directly. If it's a directory (such as /games),
// let Laravel handle the route.
if ($uri !== '/' && is_file($publicPath.$uri)) {
    // Standalone game HTML files must return X-Robots-Tag: noindex, follow
    if (preg_match('#^/games/.*\.(html|htm)$#i', $uri)) {
        header('X-Robots-Tag: noindex, follow');
        header('Content-Type: text/html; charset=UTF-8');
        readfile($publicPath.$uri);

        return true;
    }

    return false;
}

$formattedDateTime = date('D M j H:i:s Y');
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$remoteAddress = ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1').':'.($_SERVER['REMOTE_PORT'] ?? '0');

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
