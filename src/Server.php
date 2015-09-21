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
 */
class Server
{
    const POWERED_BY = 'Lexty-WebSocketServer/0.1.1';

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
     * @var string
     */
    private $pidFile;
    /**
     * @var int
     */
    private $handlers;
    /**
     * @var ApplicationInterface[][]
     */
    private $applications = [];

    /**
     * @param string                        $host
     * @param int                           $port
     * @param string                        $pidFile
     * @param int                           $handlers
     * @param ContainerBuilder|null         $container
     * @param EventDispatcherInterface|null $dispatcher
     */
    public function __construct(
        $host = 'localhost',
        $port = 8080,
        $pidFile = '/tmp/web-socket-server.pid',
        $handlers = 1,
        ContainerBuilder $container = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        $this->container = $container ?: new ContainerBuilder;
        $this->dispatcher = $dispatcher ?: new ContainerAwareEventDispatcher($this->container);

        $this->containerDefinitions($this->container);

        if (false !== strpos($host, ':')) {
            $host = '[' . $host . ']';
        }
        $this->host     = $host;
        $this->port     = $port;
        $this->pidFile  = $pidFile;
        $this->handlers = $handlers;

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

    public function run()
    {
        try {
            // open server socket
            $server = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);

            if (false === $server) {
                throw new ConnectionException(sprintf('Could not bind to tcp://%s:%s: %s', $this->host, $this->port, $errstr), $errno);
            }

            list($pid, $master, $handlers) = $this->spawnHandlers(); // create a child process

            $handlerClass = $this->container->getParameter('lexty.websocketserver.handler.class');

            if ($pid) { // master
                fclose($server);                          // master will not process incoming connections on the main socket
                $WebSocketMaster = new Master($handlers); // he will forward messages between worker`s
                $WebSocketMaster->run();
            } else { // worker
                /** @var HandlerInterface $WebSocketHandler */
                $WebSocketHandler = new $handlerClass($server, $this->container->get('lexty.websocketserver.connection_factory'), $this->dispatcher);
                $WebSocketHandler->run();
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

    private function spawnHandlers()
    {
        $pid      = $master = null;
        $handlers = [];
        for ($i = 0; $i < $this->handlers; $i++) {
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
     * @param string $path
     *
     * @return ApplicationInterface[]
     */
    private function getApplications($path)
    {
        return isset($this->applications[$path]) ? $this->applications[$path] : [];
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
}
