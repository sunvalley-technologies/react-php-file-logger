<?php

namespace SunValley\LoopUtil\FileLogger\Monolog;

use Monolog\Logger;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Stream\WritableResourceStream;
use React\Stream\WritableStreamInterface;

use function React\Promise\resolve;

class FileHandler extends StreamHandler
{

    /** @var string */
    protected $file;

    /** @var int|null */
    protected $filePermission;

    /** @var \Throwable */
    protected $exception;

    /** @var LoopInterface */
    protected $loop;

    /** @var bool */
    protected $dateSuffix;

    /**
     * @param LoopInterface $loop Event loop
     * @param string        $file Path to log file, the directory will be tried to be created on constructor. If
     *     directory cannot be created or is not writable, this constructor throws a Runtime exception.
     * @param string|int    $level The minimum logging level at which this handler will be triggered
     * @param bool          $bubble Whether the messages that are handled can bubble up the stack or not
     * @param int|null      $filePermission Optional file permissions (default (0644) are only for owner
     *                                           read/write)
     * @param int           $interrupt Interrupt signal to send. Defaults to SIGHUP = 1.
     * @param bool          $dateSuffix If a date suffix should be added after the file
     *
     * @throws \RuntimeException When directory of the file cannot be created or is not writable
     */
    public function __construct(
        LoopInterface $loop,
        string $file,
        $level = Logger::DEBUG,
        bool $bubble = true,
        ?int $filePermission = null,
        $interrupt = 1,
        $dateSuffix = true
    ) {
        $dir = dirname($file);
        @mkdir($dir, true);
        if (!is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory %s for the log file does not exist', $dir));
        }
        if (!is_writable($dir)) {
            throw new \RuntimeException(sprintf('Directory %s for the log file is not writable', $dir));
        }

        parent::__construct(null, $level, $bubble);

        $this->filePermission = $filePermission;
        $this->file           = $file;
        $this->loop           = $loop;
        $loop->addSignal(
            $interrupt,
            function () {
                $this->stream->close();
                $this->stream = null;
            }
        );
        $this->dateSuffix = $dateSuffix;
    }

    protected function openFileStream(): PromiseInterface
    {
        if ($this->stream !== null) {
            return resolve($this->stream);
        }

        if ($this->dateSuffix) {
            $filename  = pathinfo($this->file, PATHINFO_FILENAME);
            $dir       = pathinfo($this->file, PATHINFO_DIRNAME);
            $extension = pathinfo($this->file, PATHINFO_EXTENSION);

            $file = $dir . DIRECTORY_SEPARATOR . $filename . '.' . date('Y-m-d') . '.' . $extension;
        } else {
            $file = $this->file;
        }

        $fh     = fopen($file, 'anbe');
        $stream = new WritableResourceStream($fh, $this->loop);

        return resolve($stream);
    }

    protected function write(array $record): void
    {
        if ($this->exception) {
            throw $this->exception;
        }

        if ($this->stream === null) {
            $this->openFileStream()
                 ->then(
                     function (WritableStreamInterface $stream) use ($record) {
                         $this->stream = $stream;

                         $this->write($record);
                     }
                 )
                 ->otherwise(
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