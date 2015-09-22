<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Lexty\WebSocketServer\Connection\ConnectionFactory;
use Lexty\WebSocketServer\Connection\ConnectionInterface;
use Lexty\WebSocketServer\Events\ConnectionEvent;
use Lexty\WebSocketServer\Events\ErrorEvent;
use Lexty\WebSocketServer\Events\MessageEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Handler implements HandlerInterface
{
    use ReadonlyPropertiesAccessTrait;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var resource
     */
    protected $server;
    /**
     * @var resource[]
     */
    protected $clients = [];
    /**
     * @var resource[]
     */
    protected $handshakes = [];
    /**
     * @var ConnectionInterface[]
     */
    protected $connections = [];
    /**
     * @var int[]
     */
    protected $ips = [];
    /**
     * @var int
     */
    protected $pid;
    /**
     * @var int
     */
    protected $maxConnectionsByIp = 0;

    /**
     * @param resource                 $server
     * @param ConnectionFactory        $connectionFactory
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct($server, ConnectionFactory $connectionFactory, EventDispatcherInterface $dispatcher)
    {
        $this->server            = $server;
        $this->connectionFactory = $connectionFactory;
        $this->dispatcher        = $dispatcher;
        $this->pid               = posix_getpid();
    }

    /**
     * {@inheritdoc}
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionsCount()
    {
        return count($this->clients);
    }

    /**
     * {@inheritdoc}
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        static $isRunning;
        if ($isRunning) return;
        $isRunning = true;
        while (true) {
            // We are preparing an array of sockets that need to be processed
            $read = $this->clients;
            $read[] = $this->server;
            $write = $except = [];

            $this->handle($read, $write, $except);
        }
    }

    /**
     * @param array $read
     * @param array $write
     * @param array $except
     */
    protected function handle(&$read, &$write, &$except)
    {
        stream_select($read, $write, $except, null); // update an array of sockets that can be processed

        if (in_array($this->server, $read)) {        // on the server socket came a request from a new connection
            $this->readServerSocket($read);          // connect to it and make handshake, according to the WebSocket protocol
        }

        if ($read) { // Data came from existing connections
            $this->readSockets($read);
        }

        if ($write) {
            $this->writeSockets($write);
        }
    }

    /**
     * @param array $read
     */
    protected function readServerSocket(&$read)
    {
        if ($client = stream_socket_accept($this->server, -1)) {
            $conn = $this->createConnection($client);
            if ($this->maxConnectionsByIp
                && isset($this->ips[$conn->remoteAddress])
                && $this->ips[$conn->remoteAddress] > $this->maxConnectionsByIp
            ) {
                $conn->close();
            } else {
                $this->addConnection($conn);
                $this->dispatcher->dispatch(Events::CONNECT, new ConnectionEvent($conn, $this));
            }
        }

        unset($read[array_search($this->server, $read)]);
    }

    /**
     * @param array $read
     */
    protected function readSockets(&$read)
    {
        foreach ($read as $client) {
            $conn = $this->getConnection($client);
            if ($conn && !$conn->handshake && !$conn->request) {
                if ($conn->doHandshake()) {
                    $write[$conn->id] = $conn->resource;
                    $this->dispatcher->dispatch(Events::HANDSHAKE_READ, new ConnectionEvent($conn, $this));
                } else {
                    $this->removeConnection($conn);
                    $conn->close();
                    $this->dispatcher->dispatch(Events::DISCONNECT, new ConnectionEvent($conn, $this));
                }
            } else if ($conn && $conn->handshake) {
                try {
                    try {
                        $data = $conn->read();

                        if (!strlen($data)) { // connection has been closed
                            $conn->close();
                            $this->removeConnection($conn);
                            $this->dispatcher->dispatch(Events::DISCONNECT, new ConnectionEvent($conn, $this));
                            $this->dispatcher->dispatch(Events::CLOSE, new ConnectionEvent($conn, $this));
                            continue;
                        }

                        $this->dispatcher->dispatch(Events::MESSAGE, new MessageEvent($conn, $data, $this));
                    } catch (\Exception $e) {
                        $this->dispatcher->dispatch(Events::ERROR, new ErrorEvent($conn, $e, $this));
                    }
                } catch (\Exception $e) {
                    $this->handleException($e);
                }
            }
        }
    }

    /**
     * @param array $write
     */
    protected function writeSockets(&$write)
    {
        foreach ($write as $client) {
            $conn = $this->getConnection($client);
            try {
                try {
                    if (!$conn->request) { // if handshake has not been received
                        continue;          // then answer a handshake still early
                    }
                    if ($conn->doHandshake()) {
                        unset($write[$conn->id]);
                        $this->dispatcher->dispatch(Events::HANDSHAKE_SEND, new ConnectionEvent($conn, $this));
                    }

                    $this->dispatcher->dispatch(Events::OPEN, new ConnectionEvent($conn, $this));
                } catch (\Exception $e) {
                    $this->dispatcher->dispatch(Events::ERROR, new ErrorEvent($conn, $e, $this));
                }
            } catch (\Exception $e) {
                $this->handleException($e);
            }
        }
    }

    /**
     * @param resource $client Stream resource.
     *
     * @return ConnectionInterface
     */
    protected function createConnection($client)
    {
        return $this->connectionFactory->create($client);
    }

    /**
     * @param resource $client Stream resource.
     *
     * @return ConnectionInterface|null
     */
    protected function getConnection($client)
    {
        if (!isset($this->connections[intval($client)])) {
            return null;
        }
        return $this->connections[intval($client)];
    }

    /**
     * @param ConnectionInterface $conn
     */
    protected function addConnection(ConnectionInterface $conn)
    {
        $this->connections[$conn->id] = $conn;
        $this->clients[$conn->id] = $conn->resource;
        if ($this->maxConnectionsByIp) {
            @$this->ips[$conn->remoteAddress]++;
        }
    }

    /**
     * @param ConnectionInterface $conn
     */
    protected function removeConnection(ConnectionInterface $conn)
    {
        if ($this->maxConnectionsByIp && isset($this->ips[$conn->remoteAddress]) && $this->ips[$conn->remoteAddress] > 0) {
            @$this->ips[$conn->remoteAddress]--;
        }
        unset($this->clients[$conn->id], $this->handshakes[$conn->id], $this->connections[$conn->id]);
    }

    /**
     * @param \Exception $e
     */
    protected function handleException(\Exception $e)
    {
        printf("Uncaught exception '%s' with message '%s in %s:%d\n", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
        printf("Stack trace:\n");
        printf("%s\n", $e->getTraceAsString());
        printf("  thrown in %s on line %d\n", $e->getFile(), $e->getLine());
    }
}