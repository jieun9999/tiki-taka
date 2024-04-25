<?php

require __DIR__.'/vendor/autoload.php';

use Kreait\Firebase\Factory;

$dbUrlFile = '/var/www/html/Story/realtimeDatabaseUrl.txt';
$databaseUrl = file_get_contents($dbUrlFile); 

$factory = (new Factory)
    ->withServiceAccount(__DIR__.'/firebase service key.json')
    ->withDatabaseUri($databaseUrl);

$database = $factory->createDatabase();

?>