<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Lexty\WebSocketServer\Payload\PayloadInterface;

class AbstractApplication implements ApplicationInterface {
    protected $encodingChecks = true;
    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $connection, WorkerInterface $worker) {}
    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $connection = null, WorkerInterface $worker) {}
    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $connection, PayloadInterface $message, WorkerInterface $worker) {}
    /**
     * {@inheritdoc}
     */
    public function onSend(ConnectionInterface $connection, $message, WorkerInterface $worker) {}
    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $connection, \Exception $exception, WorkerInterface $worker) {
        throw $exception;
    }
}