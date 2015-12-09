<?php
use Cake\Routing\Router;

Router::plugin('Salesforce', function ($routes) {
    $routes->fallbacks('DashedRoute');
    $routes->fallbacks('InflectedRoute');
});

