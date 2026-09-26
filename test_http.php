<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$request = \Illuminate\Http\Request::create('/settings/areas', 'POST', [
    'name' => 'Testing API',
    'description' => 'Desc'
]);
$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Location: " . $response->headers->get('Location') . "\n";
$errors = session()->get('errors');
if ($errors) {
    echo "Errors: " . json_encode($errors->getBag('default')->toArray()) . "\n";
}
