<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Events;

use Lexty\WebSocketServer\Connection\ConnectionInterface;
use Lexty\WebSocketServer\HandlerInterface;

/**
 * @property \Exception $exception
 */
class ErrorEvent extends ConnectionEvent
{
    /**
     * @var \Exception
     */
    private $exception;

    /**
     * {@inheritdoc}
     * @param \Exception $payload
     */
    public function __construct(ConnectionInterface $conn, \Exception $exception, HandlerInterface $handler) {
        parent::__construct($conn, $handler);
        $this->exception = $exception;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}