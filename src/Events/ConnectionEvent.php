<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Events;

use Lexty\WebSocketServer\Connection\ConnectionInterface;
use Lexty\WebSocketServer\HandlerInterface;

/**
 * @property ConnectionInterface $connection
 * @property HandlerInterface    $handler
 */
class ConnectionEvent extends ServerEvent
{
    /**
     * @var ConnectionInterface
     */
    private $connection;
    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @param ConnectionInterface $conn
     * @param HandlerInterface    $handler
     */
    public function __construct(ConnectionInterface $conn, HandlerInterface $handler) {
        $this->connection = $conn;
        $this->handler = $handler;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * @return HandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
    }
}