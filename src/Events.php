<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

final class Events
{
    const CONNECT        = 'lexty.websocketserver.connect';
    const DISCONNECT     = 'lexty.websocketserver.disconnect';
    const HANDSHAKE_READ = 'lexty.websocketserver.handshake.read';
    const HANDSHAKE_SEND = 'lexty.websocketserver.handshake.send';
    const SEND           = 'lexty.websocketserver.send';
    const OPEN           = 'lexty.websocketserver.open';
    const CLOSE          = 'lexty.websocketserver.close';
    const MESSAGE        = 'lexty.websocketserver.message';
    const ERROR          = 'lexty.websocketserver.error';
    const SHUTDOWN       = 'lexty.websocketserver.shutdown';
}