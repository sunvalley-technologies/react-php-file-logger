<?php

namespace SunValley\LoopUtil\FileLogger;

use Monolog\Logger;
use React\Filesystem\Node\FileInterface;
use SunValley\LoopUtil\FileLogger\Monolog\FileHandler;

/**
 * Class FileLogger, a shortcut to create a logger with file handler.
 *
 * @package SunValley\LoopUtil\FileLogger
 */
class FileLogger extends Logger
{

    public function __construct(string $name, FileInterface $file, $level = Logger::DEBUG)
    {
        parent::__construct($name, [], [], null);

        $this->pushHandler(new FileHandler($file, $level));
    }
}