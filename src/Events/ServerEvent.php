<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Events;

use Lexty\WebSocketServer\ReadonlyPropertiesAccessTrait;
use Symfony\Component\EventDispatcher\Event;

class ServerEvent extends Event
{
    use ReadonlyPropertiesAccessTrait;
}