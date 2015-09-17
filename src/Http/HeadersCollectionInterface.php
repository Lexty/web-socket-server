<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Http;

interface HeadersCollectionInterface extends \IteratorAggregate, \Countable {
    /**
     * @return string[]
     */
    public function keys();

    /**
     * Returns the headers.
     *
     * @return string[][]
     */
    public function all();

    /**
     * Returns the headers.
     *
     * @return string[]
     */
    public function lines();

    /**
     * @param string $header
     *
     * @return bool
     */
    public function has($header);

    /**
     * @param string $header
     * @param string $value
     *
     * @return bool
     */
    public function contains($header, $value);

    /**
     * @param string $header
     *
     * @return string[]
     */
    public function get($header);

    /**
     * @param string $header
     *
     * @return string
     */
    public function getLine($header);

    /**
     * @return string
     */
    public function __toString();
}