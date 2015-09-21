<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Connection;

use Lexty\WebSocketServer\Http\RequestInterface;

/**
 * @property resource         $resource
 * @property int              $id
 * @property RequestInterface $request
 * @property string           $remoteAddress
 * @property bool             $handshake
 * @property string           $applicationPath
 */
interface ConnectionInterface
{
    /**
     * Send data to the connection.
     *
     * @param string    $data
     * @param bool|true $encode
     *
     * @return ConnectionInterface
     */
    public function send($data, $encode = true);

    public function read($length = 1000, $decode = true);

    /**
     * Close the connection
     */
    public function close();

    /**
     * @return resource
     */
    public function getResource();

    public function getId();

    public function isClosed();

    public function getRemoteAddress();

    /**
     * @return RequestInterface
     */
    public function getRequest();

    public function getHandshake();

    public function doHandshake();

    /**
     * @return bool|string
     */
    public function getApplicationPath();
}