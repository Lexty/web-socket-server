<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Lexty\WebSocketServer\Connection\ConnectionException;
use Lexty\WebSocketServer\Connection\ConnectionInterface;
use Lexty\WebSocketServer\Payload\PayloadException;
use Lexty\WebSocketServer\Payload\PayloadInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Handler implements HandlerInterface
{
    use ReadonlyPropertiesAccessTrait;

    /**
     * @var ContainerInterface
     */
    private $container;
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
     * @var ApplicationInterface[][]
     */
    protected $applications = [];
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
     * @var string
     */
    protected $connectionClass;
    /**
     * @var string
     */
    protected $payloadClass;

    /**
     * @param resource                 $server
     * @param ApplicationInterface[]   $applications
     * @param ContainerInterface       $container
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct($server, $applications, ContainerInterface $container, EventDispatcherInterface $dispatcher)
    {
        $this->server       = $server;
        $this->applications = $applications;
        $this->container    = $container;
        $this->dispatcher   = $dispatcher;
        $this->pid          = posix_getpid();
    }

    public function run()
    {
        static $isRunning;
        if ($isRunning) return;
        $isRunning = true;
        while (true) {
            // We are preparing an array of sockets that need to be processed
            $read = $this->clients;
            $read[] = $this->server;

            stream_select($read, $write, $except, null); // update an array of sockets that can be processed

            if (in_array($this->server, $read)) {        // on the server socket came a request from a new connection
                                                         // connect to it and make handshake, according to the WebSocket protocol
                if ($client = stream_socket_accept($this->server, -1)) {
                    $conn = $this->createConnection($client);
                    if ($this->maxConnectionsByIp
                        && isset($this->ips[$conn->remoteAddress])
                        && $this->ips[$conn->remoteAddress] > $this->maxConnectionsByIp
                    ) {
                        $conn->close();
                    } else {
                        $this->addConnection($conn);
                    }
                }

                unset($read[array_search($this->server, $read)]);
            }

            if ($read) { // Data came from existing connections
                foreach ($read as $client) {
                    $conn = $this->getConnection($client);
                    if ($conn && !$conn->handshake && !$conn->request) {
                        if ($conn->doHandshake()) {
                            $write[$conn->id] = $conn->resource;
                        } else {
                            $this->removeConnection($conn);
                            $conn->close();
                        }
                    } else if ($conn && $conn->handshake) {
                        try {
                            try {
                                $data = $conn->read();

                                if (!strlen($data)) { // connection has been closed
                                    $conn->close();
                                    $this->fireClose($conn);
                                    $this->removeConnection($conn);
                                    continue;
                                }

                                $this->fireMessage($conn, $data);
                            } catch (\Exception $e) {
                                $this->fireError($conn, $e);
                            }
                        } catch (\Exception $exception) {
                            $this->exceptionHandler($exception);
                        }
                    }
                }
            }

            if ($write) {
                foreach ($write as $client) {
                    $conn = $this->getConnection($client);
                    try {
                        try {
                            if (!$conn->request) { // if handshake has not been received
                                continue;          // then answer a handshake still early
                            }
                            if ($conn->doHandshake()) {
                                unset($write[$conn->id]);
                            }

                            $this->fireOpen($conn);
                        } catch (\Exception $e) {
                            $this->fireError($conn, $e);
                        }
                    } catch (\Exception $exception) {
                        $this->exceptionHandler($exception);
                    }
                }
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
        return $this->container->get('connection_factory')->create($client);
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
     * @param ConnectionInterface $conn
     * @param PayloadInterface    $payload
     */
    protected function fireMessage(ConnectionInterface $conn, PayloadInterface $payload)
    {
        if (!isset($this->applications[$conn->applicationPath])) {
            return;
        }
        foreach ($this->applications[$conn->applicationPath] as $application) {
            $application->onMessage($conn, $payload, $this);
        }
    }

    /**
     * @param ConnectionInterface $conn
     */
    protected function fireOpen(ConnectionInterface $conn)
    {
        if (!isset($this->applications[$conn->applicationPath])) {
            return;
        }
        foreach ($this->applications[$conn->applicationPath] as $application) {
            $application->onOpen($conn, $this);
        }
    }

    /**
     * @param ConnectionInterface $conn
     */
    protected function fireClose(ConnectionInterface $conn)
    {
        if (!isset($this->applications[$conn->applicationPath])) {
            return;
        }
        foreach ($this->applications[$conn->applicationPath] as $application) {
            $application->onClose($conn, $this);
        }
    }

    /**
     * @param ConnectionInterface $conn
     * @param \Exception          $exception
     *
     * @throws \Exception
     */
    protected function fireError(ConnectionInterface $conn, \Exception $exception)
    {
        if (!isset($this->applications[$conn->applicationPath])) {
            throw $exception;
        }
        foreach ($this->applications[$conn->applicationPath] as $application) {
            $application->onError($conn, $exception, $this);
        }
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
     * @param \Exception $e
     */
    protected function exceptionHandler(\Exception $e) {
        printf("Uncaught exception '%s' with message '%s in %s:%d\n", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
        printf("Stack trace:\n");
        printf("%s\n", $e->getTraceAsString());
        printf("  thrown in %s on line %d\n", $e->getFile(), $e->getLine());
    }
}