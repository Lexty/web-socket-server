<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Lexty\WebSocketServer\Connection\ConnectionInterface;
use Lexty\WebSocketServer\Payload\PayloadInterface;

class BaseApplication implements ApplicationInterface
{
    protected $encodingChecks = true;

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $connection, HandlerInterface $handler)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $connection = null, HandlerInterface $handler)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $connection, PayloadInterface $message, HandlerInterface $handler)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onSend(ConnectionInterface $connection, $message, HandlerInterface $handler)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $connection, \Exception $exception, HandlerInterface $handler)
    {
        throw $exception;
    }
}