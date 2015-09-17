<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Applications;

use Lexty\WebSocketServer\AbstractApplication;
use Lexty\WebSocketServer\ConnectionInterface;
use Lexty\WebSocketServer\Payload\PayloadInterface;
use Lexty\WebSocketServer\WorkerInterface;

class Chat extends AbstractApplication {
    /**
     * @var \SplObjectStorage
     */
    private $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $connection, WorkerInterface $worker) {
        $this->clients->attach($connection);
    }

    public function onMessage(ConnectionInterface $from, PayloadInterface $msg, WorkerInterface $worker) {
        /** @var ConnectionInterface $client */

        if (!$msg->checkEncoding('utf-8')) {
            return;
        }
        $message = 'пользователь #' . $from->getId() . ' (' . $worker->getPid() . '): ' . strip_tags($msg->getMessage());

        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }

    public function onClose(ConnectionInterface $connection = null, WorkerInterface $worker) {
        $this->clients->detach($connection);
    }
}