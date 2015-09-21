<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Payload;

/**
 * Interface PayloadInterface
 *
 * @property int    $length
 * @property string $message
 * @property string $type
 * @property string $error
 */
interface PayloadInterface
{
    const TYPE_TEXT   = 'text';
    const TYPE_BINARY = 'binary';
    const TYPE_CLOSE  = 'close';
    const TYPE_PING   = 'ping';
    const TYPE_PONG   = 'pong';

    /**
     * @return int
     */
    public function getLength();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getError();

    /**
     * @return string
     */
    public function __toString();

    /**
     * Check if the string is valid for the specified encoding
     *
     * @param string $encoding The expected encoding.
     *
     * @return bool true on success or false on failure.
     * @link http://php.net/manual/en/function.mb-check-encoding.php
     */
    public function checkEncoding($encoding = null);
}