#!/usr/bin/env php
<?php

use Lexty\WebSocketServer\Applications\Chat;
use Lexty\WebSocketServer\Applications\EchoServer;
use Lexty\WebSocketServer\Server;

require __DIR__ . '/../vendor/autoload.php';

$server = new Server('0.0.0.0', 8089, '/tmp/web-socket-server.pid');
$server
    ->registerApplication('/echo', new EchoServer)
    ->registerApplication('/chat', new Chat)
    ->run();