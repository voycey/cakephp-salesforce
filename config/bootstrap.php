<?php

use Cake\Cache\Cache;

Cache::config('salesforce', [
    'className' => 'Cake\Cache\Engine\FileEngine',
    'duration' => '+1 week',
    'probability' => 100,
    'path' => CACHE . 'salesforce' . DS,
]);

?>