<?php

namespace Lexty\WebSocketServer\Http;

/**
 * Query is a container for key/value pairs.
 */
class Query implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * Parameter storage.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * Constructor.
     *
     * @param array $parameters An array of parameters
     *
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * Returns the parameters.
     *
     * @return array An array of parameters
     */
    public function all()
    {
        return $this->parameters;
    }

    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     */
    public function keys()
    {
        return array_keys($this->parameters);
    }

    /**
     * Returns a parameter by name.
     *
     * @param string $path    The key
     * @param mixed  $default The default value if the parameter key does not exist
     * @param bool   $deep    If true, a path like foo[bar] will find deeper items
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function get($path, $default = null, $deep = false)
    {
        if (!$deep || false === $pos = strpos($path, '[')) {
            return array_key_exists($path, $this->parameters) ? $this->parameters[$path] : $default;
        }
        $root = substr($path, 0, $pos);
        if (!array_key_exists($root, $this->parameters)) {
            return $default;
        }
        $value = $this->parameters[$root];
        $currentKey = null;
        for ($i = $pos, $c = strlen($path); $i < $c; ++$i) {
            $char = $path[$i];
            if ('[' === $char) {
                if (null !== $currentKey) {
                    throw new \InvalidArgumentException(sprintf('Malformed path. Unexpected "[" at position %d.', $i));
                }
                $currentKey = '';
            } elseif (']' === $char) {
                if (null === $currentKey) {
                    throw new \InvalidArgumentException(sprintf('Malformed path. Unexpected "]" at position %d.', $i));
                }
                if (!is_array($value) || !array_key_exists($currentKey, $value)) {
                    return $default;
                }
                $value = $value[$currentKey];
                $currentKey = null;
            } else {
                if (null === $currentKey) {
                    throw new \InvalidArgumentException(sprintf('Malformed path. Unexpected "%s" at position %d.', $char, $i));
                }
                $currentKey .= $char;
            }
        }
        if (null !== $currentKey) {
            throw new \InvalidArgumentException(sprintf('Malformed path. Path must end with "]".'));
        }
        return $value;
    }

    /**
     * Returns true if the parameter is defined.
     *
     * @param string $key The key
     *
     * @return bool true if the parameter exists, false otherwise
     */
    public function has($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Returns an iterator for parameters.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->parameters);
    }
    /**
     * Returns the number of parameters.
     *
     * @return int The number of parameters
     */
    public function count()
    {
        return count($this->parameters);
    }

    /**
     * Returns a parameter by key.
     *
     * @param string $key The key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Returns true if the parameter is defined.
     *
     * @param string $key The key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * This method will throw an exception.
     *
     * @throws \BadMethodCallException Always
     */
    public function offsetUnset($key)
    {
        throw new \BadMethodCallException('Can not remove from read-only object.');
    }

    /**
     * This method will throw an exception.
     *
     * @throws \BadMethodCallException Always
     */
    public function offsetSet($key, $value)
    {
        throw new \BadMethodCallException('Can not set value to read-only object.');
    }
}