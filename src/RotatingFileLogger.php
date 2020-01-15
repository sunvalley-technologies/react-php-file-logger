<?php

namespace SunValley\LoopUtil\FileLogger;

use Monolog\Logger;
use React\Filesystem\Node\DirectoryInterface;
use SunValley\LoopUtil\FileLogger\Monolog\RotatingFileHandler;

/**
 * Class RotatingFileLogger,  a shortcut to create a logger with rotating file handler.
 *
 * @package SunValley\LoopUtil\FileLogger
 */
class RotatingFileLogger extends Logger
{

    public function __construct(
        string $name,
        DirectoryInterface $directory,
        int $maxFiles = 0,
        $level = Logger::DEBUG
    ) {
        parent::__construct($name, [], [], null);

        $this->pushHandler(new RotatingFileHandler($directory, $name, $maxFiles, $level));
    }
}