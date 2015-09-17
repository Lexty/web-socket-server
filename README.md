# WebSocketServer

A WebSocket implementation for PHP. Supports [RFC6455](https://tools.ietf.org/html/rfc6455).

### Installation

Using Composer:

```sh
composer require lexty/websocketserver
```
### A quick example

```php
<?php

use Lexty\WebSocketServer\Server;
use Lexty\WebSocketServer\AbstractApplication;

// Make sure composer dependencies have been installed
require __DIR__ . '/vendor/autoload.php';

/**
 * chat.php
 * Send any incoming messages to all connected clients
 */
class Chat extends AbstractApplication {
    private $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $connection, WorkerInterface $worker)
    {
        $this->clients->attach($connection);
    }

    public function onMessage(ConnectionInterface $from, PayloadInterface $msg, WorkerInterface $worker)
    {
        if (!$msg->checkEncoding('utf-8')) {
            return;
        }
        $message = 'user #' . $from->getId() . ' (' . $worker->getPid() . '): ' . strip_tags($msg->getMessage());

        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }

    public function onClose(ConnectionInterface $connection = null, WorkerInterface $worker)
    {
        $this->clients->detach($connection);
    }
}

$server = new Server('0.0.0.0', 8089, '/tmp/web-socket-server.pid');
$server
    ->registerApplication('/chat', new Chat)
    ->run();
```

    $ php chat.php

```javascript
    // Then some JavaScript in the browser:
    var conn = new WebSocket('ws://localhost:8080/chat');
    conn.onmessage = function(e) { console.log(e.data); };
    conn.send('Hello Me!');
```