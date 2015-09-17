<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Http;

use Lexty\WebSocketServer\ReadonlyPropertiesAccessTrait;

/**
 * @property-read string                     $method
 * @property-read string                     $path
 * @property-read string                     $protocol
 * @property-read HeadersCollectionInterface $headers
 * @property-read string                     $body
 */
class Request implements RequestInterface {
    use ReadonlyPropertiesAccessTrait;

    /** @var string Request method. */
    private $method;
    /** @var string Request path. */
    private $path;
    /** @var string Request protocol. */
    private $protocol;
    /** @var HeadersCollectionInterface A container for HTTP headers. */
    private $headers;
    /** @var string Request body. */
    private $body;

    /**
     * Create instance of Request class by request raw string.
     *
     * @param string $rawData Request raw string.
     *
     * @return Request
     * @throws InvalidRequestException
     */
    public static function createFromRawData($rawData) {
        if (false === $sep = strpos($rawData, "\r\n\r\n")) {
            throw new InvalidRequestException('End of headers not detected.');
        }
        $rawHeaders = trim(substr($rawData, 0, $sep));
        $body = trim(substr($rawData, $sep + 1));
        $lines = explode("\r\n", $rawHeaders);
        $first = explode(' ', trim(array_shift($lines)));
        if (count($first) !== 3) {
            throw new InvalidRequestException(sprintf('Malformed first line of request: "%s".', implode(' ', $first)));
        }
        list($method, $path, $protocol) = $first;

        $headers = [];
        foreach ($lines as $line) {
            if (false === $sep = strpos($line, ':')) {
                throw new InvalidRequestException(sprintf('Malformed header line "%s".', $line));
            }
            $headers[strtolower(trim(substr($line, 0, $sep)))] = explode(',', trim(substr($line, $sep + 1)));
        }

        $request = new self($method, $path, $protocol, $headers, $body);
        unset($method, $path, $protocol, $headers, $body, $rawData, $rawHeaders, $lines, $line, $first, $sep);
        return $request;
    }

    /**
     * @param string $method
     * @param string $path
     * @param string $protocol
     * @param array  $headers
     * @param string $body
     */
    public function __construct($method, $path, $protocol, array $headers, $body = '') {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->protocol = $protocol;
        $this->headers = new HeadersCollection($headers);
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocol() {
        return $this->protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() {
        $string = "{$this->getMethod()} {$this->getPath()} {$this->getProtocol()}\r\n";
        $string .= (string) $this->getHeaders();
        $string .= "\r\n\r\n";
        $string .= $this->getBody();
        return $string;
    }
}