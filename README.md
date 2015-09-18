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
use Lexty\WebSocketServer\BaseApplication;

// Make sure composer dependencies have been installed
require __DIR__ . '/vendor/autoload.php';

/**
 * chat.php
 * Send any incoming messages to all connected clients
 */
class Chat extends BaseApplication 
{
    private $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn, HandlerInterface $handler)
    {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, PayloadInterface $msg, HandlerInterface $handler)
    {
        if (!$msg->checkEncoding('utf-8')) {
            return;
        }
        $message = 'user #' . $from->id . ' (' . $handler->pid . '): ' . strip_tags($msg);

        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }

    public function onClose(ConnectionInterface $conn = null, HandlerInterface $handler)
    {
        $this->clients->detach($conn);
    }
}

$server = new Server('0.0.0.0', 8080, '/tmp/web-socket-server.pid');
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

You can pass additional parameters in the server connection. For example, for authorization.
 
 
```javascript
// In the browser:
var conn = new WebSocket('ws://localhost:8080/chat?user=login&auth=token');
```

```php
<?php

use Lexty\WebSocketServer\BaseApplication;

class App extends BaseApplication 
{
    public function onOpen(ConnectionInterface $conn, HandlerInterface $handler)
    {
        $user = $conn->request->query['user'];
        $auth = $conn->request->query['auth'];
        if (!$user || !$auth) { // some authorization
            $conn->close();
        }
    }
}
```