<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Lexty\WebSocketServer\Connection\ConnectionException;
use Lexty\WebSocketServer\Events\ConnectionEvent;
use Lexty\WebSocketServer\Events\ErrorEvent;
use Lexty\WebSocketServer\Events\MessageEvent;
use Lexty\WebSocketServer\Events\ServerEvent;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @link https://tools.ietf.org/html/rfc6455 RFC6455
 *
 * @property ContainerInterface       $container
 * @property EventDispatcherInterface $dispatcher
 * @property string                   $host
 * @property int                      $port
 * @property int                      $handlersCount
 * @property int                      $pid
 * @property ApplicationInterface[][] $applications
 */
class Server
{
    use ReadonlyPropertiesAccessTrait;

    const POWERED_BY = 'Lexty-WebSocketServer/0.1.2';

    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $port;
    /**
     * @var int
     */
    private $pid;
    /**
     * @var int
     */
    private $handlersCount;
    /**
     * @var ApplicationInterface[][]
     */
    private $applications = [];

    /**
     * @param string                        $host
     * @param int                           $port
     * @param string                        $pidFile
     * @param int                           $handlersCount
     * @param ContainerBuilder|null         $container
     * @param EventDispatcherInterface|null $dispatcher
     */
    public function __construct(
        $host = 'localhost',
        $port = 8080,
        $pidFile = '/tmp/web-socket-server.pid',
        $handlersCount = 1,
        ContainerBuilder $container = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        $this->container = $container ?: new ContainerBuilder;
        $this->dispatcher = $dispatcher ?: new ContainerAwareEventDispatcher($this->container);

        $this->containerDefinitions($this->container);

        if (false !== strpos($host, ':')) {
            $host = '[' . $host . ']';
        }
        $this->host          = $host;
        $this->port          = $port;
        $this->pidFile       = $pidFile;
        $this->handlersCount = $handlersCount;

        $this->dispatcher->addListener(Events::OPEN, [$this, 'onOpen'], 10);
        $this->dispatcher->addListener(Events::CLOSE, [$this, 'onClose'], 10);
        $this->dispatcher->addListener(Events::MESSAGE, [$this, 'onMessage'], 10);
        $this->dispatcher->addListener(Events::ERROR, [$this, 'onError'], 10);
    }

    /**
     * @param string               $name
     * @param ApplicationInterface $application
     *
     * @return $this
     */
    public function registerApplication($name, ApplicationInterface $application)
    {
        $this->applications[trim($name, '/')][] = $application;
        return $this;
    }

    /**
     * Start server
     *
     * @throws \Exception
     */
    public function run()
    {
        try {
            // open server socket
            $server = $this->createSocketServer($this->host, $this->port);

            list($pid, $master, $handlers) = $this->spawnHandlers($this->handlersCount); // create a child process

            $handlerClass = $this->container->getParameter('lexty.websocketserver.handler.class');

            if ($pid) { // master
                fclose($server);                          // master will not process incoming connections on the main socket
                $webSocketMaster = new Master($handlers); // he will forward messages between worker`s
                $webSocketMaster->run();
            } else { // worker
                /** @var HandlerInterface $webSocketHandler */
                $webSocketHandler = new $handlerClass($server, $this->container->get('lexty.websocketserver.connection_factory'), $this->dispatcher);
                $webSocketHandler->run();
            }
            $this->dispatcher->dispatch(Events::SHUTDOWN, new ServerEvent);
        } catch (\Exception $e) {
            $this->dispatcher->dispatch(Events::SHUTDOWN, new ServerEvent);
            throw $e;
        }
    }

    /**
     * @param MessageEvent $event
     */
    public function onMessage(MessageEvent $event)
    {
        foreach ($this->getApplications($event->connection->applicationPath) as $application) {
            $application->onMessage($event->connection, $event->payload, $event->handler);
        }
    }

    /**
     * @param ConnectionEvent $event
     */
    public function onOpen(ConnectionEvent $event)
    {
        foreach ($this->getApplications($event->connection->applicationPath) as $application) {
            $application->onOpen($event->connection, $event->handler);
        }
    }

    /**
     * @param ConnectionEvent $event
     */
    public function onClose(ConnectionEvent $event)
    {
        foreach ($this->getApplications($event->connection->applicationPath) as $application) {
            $application->onClose($event->connection, $event->handler);
        }
    }

    /**
     * @param ErrorEvent $event
     *
     * @throws \Exception
     */
    public function onError(ErrorEvent $event)
    {
        foreach ($this->getApplications($event->connection->applicationPath) as $application) {
            $application->onError($event->connection, $event->exception, $event->handler);
        }
    }

    /**
     * @param string $host
     * @param int    $port
     *
     * @return resource
     */
    private function createSocketServer($host, $port) {
        $server = stream_socket_server("tcp://$host:$port", $errno, $errstr);
        if (false === $server) {
            throw new ConnectionException(sprintf('Could not bind to tcp://%s:%s: %s', $host, $port, $errstr), $errno);
        }
        return $server;
    }

    /**
     * @param int $count
     *
     * @return array
     */
    private function spawnHandlers($count)
    {
        $pid = $master = null;
        $handlers = [];
        for ($i = 0; $i < $count; $i++) {
            // creating twin sockets, they will be contacted by the master and the worker is
            $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

            $pid = pcntl_fork(); // fork
            if (-1 === $pid) {
                throw new \RuntimeException('Could not fork process');
            } elseif ($pid) { // master
                fclose($pair[0]);
                $handlers[$pid] = $pair[1]; // one of the pair is in the master
            } else { // worker
                fclose($pair[1]);
                $master = $pair[0];         // the second is in the worker
                break;
            }
        }

        return [$pid, $master, $handlers];
    }

    /**
     * @param ContainerBuilder $container
     */
    private function containerDefinitions(ContainerBuilder $container)
    {
        if (!$container->hasParameter('lexty.websocketserver.handler.class')) {
            $container->setParameter('lexty.websocketserver.handler.class', 'Lexty\\WebSocketServer\\Handler');
        }

        if (!$container->hasParameter('lexty.websocketserver.payload.class')) {
            $container->setParameter('lexty.websocketserver.payload.class', 'Lexty\\WebSocketServer\\Payload\\Payload');
        }
        if (!$container->hasParameter('lexty.websocketserver.payload_factory.class')) {
            $container->setParameter('lexty.websocketserver.payload_factory.class', 'Lexty\\WebSocketServer\\Payload\\PayloadFactory');
        }
        if (!$container->has('lexty.websocketserver.payload_factory')) {
            $container
                ->register('lexty.websocketserver.payload_factory', $container->getParameter('lexty.websocketserver.payload_factory.class'))
                ->addArgument('%lexty.websocketserver.payload.class%');
        }

        if (!$container->hasParameter('lexty.websocketserver.connection.class')) {
            $container->setParameter('lexty.websocketserver.connection.class', 'Lexty\\WebSocketServer\\Connection\\Connection');
        }
        if (!$container->hasParameter('lexty.websocketserver.connection_factory.class')) {
            $container->setParameter('lexty.websocketserver.connection_factory.class', 'Lexty\\WebSocketServer\\Connection\\ConnectionFactory');
        }
        if (!$container->has('lexty.websocketserver.connection_factory')) {
            $container
                ->register('lexty.websocketserver.connection_factory', $container->getParameter('lexty.websocketserver.connection_factory.class'))
                ->addArgument(new Reference('lexty.websocketserver.payload_factory'))
                ->addArgument('%lexty.websocketserver.connection.class%');
        }
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return int
     */
    public function getHandlersCount()
    {
        return $this->handlersCount;
    }

    /**
     * @param string|null $path
     *
     * @return ApplicationInterface[][][]|ApplicationInterface[]
     */
    private function getApplications($path = null)
    {
        return null === $path ? $this->applications : (isset($this->applications[$path]) ? $this->applications[$path] : []);
    }
}
