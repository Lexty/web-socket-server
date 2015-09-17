<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Lexty\WebSocketServer\Payload\PayloadInterface;

interface ApplicationInterface {
    /**
     * When a new connection is opened it will be passed to this method.
     *
     * @param ConnectionInterface $connection
     * @param WorkerInterface     $worker
     */
    public function onOpen(ConnectionInterface $connection, WorkerInterface $worker);

    /**
     * This is called before or after a socket is closed (depends on how it's closed).
     *
     * SendMessage to $connection will not result in an error if it has already been closed.
     *
     * @param ConnectionInterface $connection
     * @param WorkerInterface     $worker
     */
    public function onClose(ConnectionInterface $connection = null, WorkerInterface $worker);

    /**
     * Triggered when a client sends data through the socket.
     *
     * @param ConnectionInterface $connection
     * @param PayloadInterface    $message
     * @param WorkerInterface     $worker
     */
    public function onMessage(ConnectionInterface $connection, PayloadInterface $message, WorkerInterface $worker);

    /**
     * @param ConnectionInterface $connection
     * @param string              $message
     * @param WorkerInterface     $worker
     *
     * @return mixed
     */
    public function onSend(ConnectionInterface $connection, $message, WorkerInterface $worker);

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method.
     *
     * @param ConnectionInterface $connection
     * @param \Exception          $exception
     * @param WorkerInterface     $worker
     *
     * @return mixed
     */
    public function onError(ConnectionInterface $connection, \Exception $exception, WorkerInterface $worker);
}