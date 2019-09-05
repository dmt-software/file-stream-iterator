<?php

namespace DMT\Stream\Reader\Stream\Wrapper;

class ReadStreamWrapper
{
    /** @var resource[] */
    private static $streams = [];

    /** @var resource */
    private $current;

    /**
     * Register a stream as stream wrapper.
     *
     * @param resource $stream
     * @return string
     */
    public static function register($stream): string
    {
        self::$streams[$path = 'dmt://' . uniqid('dmt://')] = $stream;

        if (!in_array('dmt', stream_get_wrappers(), true)) {
            stream_register_wrapper($protocol ?? 'dmt', self::class);
        }

        return $path;
    }

    /**
     * Open a stream.
     *
     * @param string $path
     * @return bool|resource
     */
    public function stream_open(string $path)
    {
        return $this->getStream($path) ?? false;
    }

    /**
     * Read from stream.
     *
     * @param int $length
     * @return string|null
     */
    public function stream_read(int $length): ?string
    {
        $stream = $this->getStream();

        if (!feof($stream)) {
            $data = fread($stream, $length);
        }

        return isset($data) ? $data : null;
    }

    /**
     * Close the stream.
     *
     * @return bool
     */
    public function stream_close(): bool
    {
        return fclose($this->getStream());
    }

    /**
     * Get stream metadata.
     *
     * @param string $path
     * @return array
     */
    public function url_stat(string $path): array
    {
        $stream = self::$streams[$path] ?? null;

        if (null === $stream) {
            throw new \RuntimeException('Could not use stream');
        }

        $metadata = stream_get_meta_data($stream);

        if (!preg_match('~^(r|[wc]\+)$~', $metadata['mode'])) {
            throw new \RuntimeException('Could not read from stream');
        }

        return $metadata;
    }

    /**
     * Get the (current) stream.
     *
     * @param string|null $path
     * @return resource
     * @throws \RuntimeException
     */
    private function getStream(?string $path = null)
    {
        if (null !== $path) {
            $this->current = self::$streams[$path] ?? null;
        }

        if (null === $this->current) {
            throw new \RuntimeException('Could not use stream');
        }

        return $this->current;
    }
}