<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Http;

use Lexty\WebSocketServer\ReadonlyPropertiesAccessTrait;

class Response implements ResponseInterface
{
    use ReadonlyPropertiesAccessTrait;

    const HTTP_SWITCHING_PROTOCOLS = 101;
    const HTTP_OK                  = 200;
    const HTTP_BAD_REQUEST         = 400;
    const HTTP_NOT_FOUND           = 404;
    /**
     * @var string[]
     */
    private static $statusCodes = [
        self::HTTP_SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::HTTP_OK                  => 'OK',
        self::HTTP_BAD_REQUEST         => 'Bad Request',
        self::HTTP_NOT_FOUND           => 'Not Found',
    ];
    /** @var int Response code. */
    private $code;
    /** @var string Response protocol. */
    private $protocol = 'HTTP/1.0';
    /** @var HeadersCollectionInterface A container for HTTP headers. */
    private $headers;
    /** @var string Response body. */
    private $body;

    /**
     * @param int    $statusCode
     * @param array  $headers
     * @param string $body
     */
    public function __construct($statusCode, array $headers, $body = '')
    {
        if (!$this->verifyStatusCode($statusCode)) {
            throw new \InvalidArgumentException(sprintf('Status code "%s" are not supported.', $statusCode));
        }
        $this->code    = $statusCode;
        $this->headers = new HeadersCollection($headers);
        $this->body    = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusMessage()
    {
        return self::$statusCodes[$this->code];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $string = "{$this->getProtocol()} {$this->getStatusCode()} {$this->getStatusMessage()}\r\n";
        $string .= (string)$this->getHeaders();
        $string .= "\r\n";
        $string .= $this->getBody();
        return $string;
    }

    /**
     * @param int $statusCode
     *
     * @return bool
     */
    private function verifyStatusCode($statusCode)
    {
        return is_int($statusCode) && isset(self::$statusCodes[$statusCode]);
    }
}