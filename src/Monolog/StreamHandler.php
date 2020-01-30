<?php

namespace SunValley\LoopUtil\FileLogger\Monolog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use React\Stream\WritableStreamInterface;

class StreamHandler extends AbstractProcessingHandler
{

    /** @var WritableStreamInterface|null */
    protected $stream;

    public function __construct(?WritableStreamInterface $stream, $level = Logger::DEBUG, bool $bubble = true)
    {
        if (!$this->allowingNullStream() && $stream === null) {
            throw new \InvalidArgumentException('This handler does not support not having a stream');
        }

        parent::__construct($level, $bubble);
        $this->stream = $stream;
    }

    /** @inheritDoc */
    protected function write(array $record): void
    {
        $this->stream->write((string)$record['formatted']);
    }

    protected function allowingNullStream(): bool
    {
        return false;
    }

    /**
     * @return WritableStreamInterface|null
     */
    public function getStream(): ?WritableStreamInterface
    {
        return $this->stream;
    }

    /**
     * Create a monolog logger from this handler
     *
     * @param string $name
     *
     * @return Logger
     */
    public function createLogger(string $name): Logger
    {
        return new Logger($name, [$this], [], null);
    }
}