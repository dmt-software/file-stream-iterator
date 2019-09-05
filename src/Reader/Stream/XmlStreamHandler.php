<?php

namespace DMT\Stream\Reader\Stream;

/**
 * Class XmlStreamHandler
 *
 * This handler will read a xml stream from a resource opened with fopen() f.i.
 */
class XmlStreamHandler
{
    /** @var \XMLReader */
    private $handler;

    /** @var array */
    private $context;

    /**
     * XmlStreamHandler constructor.
     *
     * @param resource $stream
     * @param mixed $context
     *
     * @throws \TypeError
     * @throws \RuntimeException
     */
    public function __construct($stream, $context)
    {
        if (!is_resource($stream)) {
            throw new \TypeError(
                'XmlStreamHandler expects the first argument to be a resource'
            );
        }

        $this->handler = new \XMLReader();
        $this->handler->open(Wrapper\ReadStreamWrapper::register($stream));

        $this->context = $context;
    }

    /**
     * Read a part of the file.
     *
     * @return iterable|null
     */
    public function read(): ?iterable
    {
        try {
            yield from $this->readXml();
        } catch (\Exception $exception) {
            // @todo throw ReadException
        } finally {
            $this->handler->close();
        }
    }

    /**
     * Read chunks of the xml.
     *
     * @return iterable|null
     * @throws \ErrorException
     */
    private function readXml(): ?iterable
    {
        $processed = 0;

        do {
            $xml = $this->handler->readOuterXml();

            if ($e = libxml_get_last_error()) {
                throw new \ErrorException($e->message, $e->code, $e->level, $e->file, $e->line);
            }

            if ($xml) {
                yield $processed++ => $xml;
            }
        } while ($this->handler->next($this->handler->localName) !== false);
    }
}