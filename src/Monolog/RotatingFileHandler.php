<?php

namespace SunValley\LoopUtil\FileLogger\Monolog;

use Monolog\Logger;
use React\Filesystem\FilesystemInterface;
use React\Filesystem\Node\DirectoryInterface;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\NodeInterface;
use React\Promise\PromiseInterface;
use function React\Promise\all;
use function React\Promise\resolve;

class RotatingFileHandler extends FileHandler
{

    public const FILE_PER_DAY = 'Y-m-d';
    public const FILE_PER_MONTH = 'Y-m';
    public const FILE_PER_YEAR = 'Y';

    protected $directory;
    protected $filename;
    protected $maxFiles;
    protected $mustRotate;
    protected $nextRotation;
    protected $filenameFormat;
    protected $dateFormat;
    protected $extension = '.log';

    /**
     * @param DirectoryInterface $directory      Directory that this file handler will place the logs
     * @param string             $filename
     * @param int                $maxFiles       The maximal amount of files to keep (0 means unlimited)
     * @param string|int         $level          The minimum logging level at which this handler will be triggered
     * @param bool               $bubble         Whether the messages that are handled can bubble up the stack or not
     * @param int|null           $filePermission Optional file permissions (default (0644) are only for owner
     *                                           read/write)
     *
     * @throws \Exception
     */
    public function __construct(
        DirectoryInterface $directory,
        string $filename,
        int $maxFiles = 0,
        $level = Logger::DEBUG,
        bool $bubble = true,
        ?int $filePermission = null
    ) {
        $this->filename       = $filename;
        $this->maxFiles       = $maxFiles;
        $this->nextRotation   = new \DateTimeImmutable('tomorrow');
        $this->filenameFormat = '{filename}-{date}{extension}';
        $this->dateFormat     = static::FILE_PER_DAY;
        $this->directory      = $directory;

        parent::__construct($this->getTimedFile(), $level, $bubble, $filePermission);
    }

    /**
     * Set file extension
     *
     * @param string $extension Such as ".log" which is the default.
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        parent::close();

        if (true === $this->mustRotate) {
            $this->rotate();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        parent::reset();

        if (true === $this->mustRotate) {
            $this->rotate();
        } else {
            $this->file   = $this->getTimedFile();
            $this->stream = null;
        }
    }

    public function setDateFormat(string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;
        $this->file       = $this->getTimedFile();
        $this->close();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        if ($this->nextRotation <= $record['datetime']) {
            $this->mustRotate = true;
            $this->close();
        }

        parent::write($record);
    }

    /**
     * Rotates the files.
     */
    protected function rotate(): PromiseInterface
    {
        // update filename
        $this->file         = $this->getTimedFile();
        $this->nextRotation = new \DateTimeImmutable('tomorrow');

        // skip GC of old logs if files are unlimited
        if (0 === $this->maxFiles) {
            return resolve();
        }

        return $this->directory->ls()->then(
            function (array $list) {

                /** @var NodeInterface[] $files */
                $files     = [];
                $filename  = $this->filename;
                $extension = $this->extension;

                /** @var NodeInterface $node */
                foreach ($list as $node) {
                    $name = $node->getName();
                    if (strpos($name, $filename) === 0 && ($extension &&
                            strrpos($name, $extension) === strlen($name) - strlen($extension)) || !$extension) {
                        $files[] = $name;
                    }
                }

                if ($this->maxFiles >= count($files)) {
                    // no files to remove
                    return;
                }

                // Sorting the files by name to remove the older ones
                usort(
                    $files,
                    function (NodeInterface $a, NodeInterface $b) {
                        return strcmp($b->getName(), $a->getName());
                    }
                );

                $promises = [];
                $files    = array_slice($files, $this->maxFiles);
                foreach ($files as $file) {
                    if ($file instanceof FileInterface) {
                        $promises[] = $file->remove();
                    }
                }

                if ($promises) {
                    return all($promises);
                }

                return resolve();
            }
        );


    }

    protected function getTimedFile(): FileInterface
    {
        $fileInfo      = pathinfo($this->filename);
        $timedFilename = str_replace(
            ['{filename}', '{date}', '{extension}'],
            [$this->filename, $this->getDate(), $this->extension],
            $this->filenameFormat
        );
        
        /** @var FilesystemInterface $filesystem */
        $filesystem = $this->directory->getFilesystem();

        return $filesystem->file($this->directory->getPath() . '/' . $timedFilename);
    }

    /**
     * Set the next rotation time. Internally this is once a day.
     *
     * @param \DateTimeImmutable $nextRotation
     */
    public function setNextRotation(\DateTimeImmutable $nextRotation): void
    {
        $this->nextRotation = $nextRotation;
    }

    protected function openFileStream(): PromiseInterface
    {
        if ($this->stream !== null) {
            return resolve($this->stream);
        }

        $closure = \Closure::fromCallable([$this, 'callParentFileStream']);

        return $this->directory->createRecursive()->then($closure, $closure);
    }

    protected function callParentFileStream(): PromiseInterface
    {
        return parent::openFileStream();
    }

    public function getDate(): string
    {
        return date($this->dateFormat);
    }

    /**
     * Returns date format
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }
}