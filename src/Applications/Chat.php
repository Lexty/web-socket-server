<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Applications;

use Lexty\WebSocketServer\BaseApplication;
use Lexty\WebSocketServer\ConnectionInterface;
use Lexty\WebSocketServer\Payload\PayloadInterface;
use Lexty\WebSocketServer\HandlerInterface;

class Chat extends BaseApplication
{
    /**
     * @var \SplObjectStorage
     */
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
        /** @var ConnectionInterface $client */

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