<?php

require __DIR__ . '/vendor/autoload.php';

echo 'WebSocket server is running. Press Ctrl+C for stop.' . PHP_EOL . PHP_EOL;

$server = new \Lexty\WebSocketServer\Server('0.0.0.0', 8089, __DIR__ . '/pid');
$server
    ->registerApplication('/echo', new \Lexty\WebSocketServer\Applications\EchoServer)
    ->registerApplication('/chat', new \Lexty\WebSocketServer\Applications\Chat)
    ->run();