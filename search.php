<?php

use GuzzleHttp\Client;

require 'vendor/autoload.php';

$client = new Client( [ 'base_uri' => 'https://www.flaticon.com'] );
$response = $client->request('GET', '/search?word=car');

echo $response->getBody();