<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer;

use Lexty\WebSocketServer\Exceptions\UndefinedPropertyException;

trait ReadonlyPropertiesAccessTrait
{
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            throw new UndefinedPropertyException(sprintf('Undefined property "%s::$%s".', get_called_class(), $name));
        }
    }
}