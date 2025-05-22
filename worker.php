<?php

use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\Goridge\StreamRelay;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequestFactory;

require 'vendor/autoload.php';

$relay = new StreamRelay(STDIN, STDOUT);
$worker = new PSR7Worker($relay);

while ($request = $worker->waitRequest()) {
    try {
        $path = $request->getUri()->getPath();

        // Normalize and resolve file path
        $file = realpath(__DIR__ . $path);
        $baseDir = realpath(__DIR__);

        // Prevent access outside the backend folder
        if (!$file || strpos($file, $baseDir) !== 0 || !file_exists($file) || !str_ends_with($file, '.php')) {
            $worker->respond(new HtmlResponse("404 Not Found", 404));
            continue;
        }

        // Handle GET/POST input
        $_GET = $request->getQueryParams();
        $_POST = $request->getParsedBody() ?? [];
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();

        ob_start();
        include $file;
        $output = ob_get_clean();

        $worker->respond(new HtmlResponse($output));
    } catch (\Throwable $e) {
        $worker->respond(new HtmlResponse("Error: " . $e->getMessage(), 500));
    }
}
