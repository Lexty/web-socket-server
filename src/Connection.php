<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Lexty\WebSocketServer\Http\Response;
use Lexty\WebSocketServer\Payload\Payload;
use Lexty\WebSocketServer\Http\Request;
use Lexty\WebSocketServer\Http\RequestInterface;

class Connection implements ConnectionInterface
{
    use ReadonlyPropertiesAccessTrait;

    /**
     * @var resource
     */
    private $connection;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var bool
     */
    private $handshake = false;
    /**
     * @var int
     */
    private $webSocketVersion;
    /**
     * @var string[]
     */
    private $webSocketProtocols = [];
    /**
     * @var string
     */
    private $remoteAddress;

    /**
     * @param resource $connection
     */
    public function __construct($connection)
    {
        if (!is_resource($connection) || get_resource_type($connection) !== 'stream') {
            throw new \InvalidArgumentException('First parameter must be a valid stream resource.');
        }
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function send($data, $raw = false)
    {
        $data = $raw ? (string)$data : Payload::encode((string)$data);

        $status = false;
        $write  = [$this->connection];
        if (stream_select($read, $write, $except, 0)) {
            foreach ($write as $client) {
                $status = fwrite($client, $data);
            }
        }
        return false !== $status;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length = 1000)
    {
        return fread($this->connection, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (is_resource($this->connection)) {
            stream_socket_shutdown($this->connection, STREAM_SHUT_RDWR);
            stream_set_blocking($this->connection, false);
            fclose($this->connection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->connection;
    }

    public function getId()
    {
        return intval($this->getResource());
    }

    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return string[]
     */
    public function getWebSocketProtocols()
    {
        return $this->webSocketProtocols;
    }

    /**
     * @return int
     */
    public function getWebSocketVersion()
    {
        return $this->webSocketVersion;
    }

    /**
     * @return bool
     */
    public function isClosed()
    {
        return !is_resource($this->connection);
    }

    /**
     * @return string
     */
    public function getRemoteAddress()
    {
        if (null === $this->remoteAddress) {
            $this->remoteAddress = $this->parseAddress(stream_socket_get_name($this->connection, true));
        }
        return $this->remoteAddress;
    }

    private function parseAddress($address)
    {
        return trim(substr($address, 0, strrpos($address, ':')), '[]');
    }

    /**
     * @return bool
     */
    public function doHandshake()
    {
        /** @var Request $request */
        /** @var Connection $conn */
        $request = $this->request;
        if (!$request) {
            // считываем загаловки из соединения
            $rawRequest = $this->read(10000);
            $request    = Request::createFromRawData($rawRequest);

            $this->request = $request;

            return $request && $request->headers->getLine('Sec-WebSocket-Key');
        } else {
            $key = $request->getHeaders()->getLine('Sec-WebSocket-Key');
            //отправляем заголовок согласно протоколу вебсокета
            $response = new Response(Response::HTTP_SWITCHING_PROTOCOLS, [
                'Upgrade'              => 'websocket',
                'Connection'           => 'Upgrade',
                'Sec-WebSocket-Accept' => base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'))),
                'X-Powered-By'         => Server::POWERED_BY,
            ]
            );
            if (false !== fwrite($this->connection, (string)$response)) {
//            if ($this->send($response, true)) {
                $this->handshake = true;
            }

            return $this->handshake;
        }
    }

    /**
     * @return bool
     */
    public function getHandshake()
    {
        return $this->handshake;
    }

    /**
     * @return bool|string
     */
    public function getApplicationPath()
    {
        return $this->request ? trim($this->request->getPath(), '/') : false;
    }

    public function getPath()
    {
        return 'chat';
    }
}