<?php

namespace SunValley\LoopUtil\FileLogger\Monolog;

use Monolog\Logger;
use React\Filesystem\Node\FileInterface;
use React\Promise\PromiseInterface;
use React\Stream\WritableStreamInterface;
use function React\Promise\resolve;

class FileHandler extends StreamHandler
{

    /** @var FileInterface */
    protected $file;

    /** @var int|null */
    protected $filePermission;

    /** @var \Throwable */
    protected $exception;

    /**
     * @param FileInterface $file                File that logs will be written into
     * @param string|int    $level               The minimum logging level at which this handler will be triggered
     * @param bool          $bubble              Whether the messages that are handled can bubble up the stack or not
     * @param int|null      $filePermission      Optional file permissions (default (0644) are only for owner
     *                                           read/write)
     *
     */
    public function __construct(
        FileInterface $file,
        $level = Logger::DEBUG,
        bool $bubble = true,
        ?int $filePermission = null
    ) {
        parent::__construct(null, $level, $bubble);

        $this->filePermission = $filePermission;
        $this->file           = $file;
    }

    protected function openFileStream(): PromiseInterface
    {
        if ($this->stream !== null) {
            return resolve($this->stream);
        }

        return $this->file->open('an', $this->filePermission);
    }

    protected function write(array $record): void
    {
        if ($this->exception) {
            throw $this->exception;
        }

        if ($this->stream === null) {
            $this->openFileStream()->then(
                function (WritableStreamInterface $stream) use ($record) {
                    $this->stream = $stream;

                    $this->write($record);
                }
            )->otherwise(
                function (\Throwable $e) {
                    $this->exception = $e;
                }
            );

            return;
        }

        parent::write($record);
    }

    /**
     * @return \Throwable
     */
    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    public function close(): void
    {
        if ($this->stream !== null) {
            $this->stream->close();
            $this->stream = null;
        }
    }

    protected function allowingNullStream(): bool
    {
        return true;
    }
}