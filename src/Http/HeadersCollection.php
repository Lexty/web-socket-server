<?php
/**
 * @author Alexandr Medvedev <medvedevav@niissu.ru>
 */

namespace Lexty\WebSocketServer\Http;

/**
 * HeadersCollection is a container for HTTP headers.
 */
class HeadersCollection implements HeadersCollectionInterface
{
    /**
     * @var string[][] Cached HTTP header collection with lowercase key to values.
     */
    private $headers = [];
    /**
     * @var string[] Actual key to list of values per header.
     */
    private $headerLines = [];

    /**
     * @param array $headers
     */
    public function __construct(array $headers = [])
    {
        $this->headerLines = $this->headers = [];
        foreach ($headers as $header => $value) {
            $header = trim($header);
            $name   = strtolower($header);
            if (!is_array($value)) {
                $value                      = trim($value);
                $this->headers[$name][]     = $value;
                $this->headerLines[$header] = $value;
            } else {
                foreach ($value as $v) {
                    $v                      = trim($v);
                    $this->headers[$name][] = $v;
                }
                $this->headerLines[$header] = implode(', ', $value);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return array_keys($this->headers);
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function lines()
    {
        return $this->headerLines;
    }

    /**
     * {@inheritdoc}
     */
    public function has($header)
    {
        return isset($this->headers[strtolower($header)]);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($header, $value)
    {
        return in_array($value, $this->get($header));
    }

    /**
     * {@inheritdoc}
     */
    public function get($header)
    {
        $header = strtolower($header);
        return isset($this->headers[$header]) ? $this->headers[$header] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLine($header)
    {
        return implode(', ', $this->get($header));
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (!$this->headers) {
            return '';
        }
        $max     = max(array_map('strlen', array_keys($this->headers))) + 1;
        $content = '';
        ksort($this->headers);
        foreach ($this->headers as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
            }
        }
        return $content;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->headers);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->headers);
    }
}