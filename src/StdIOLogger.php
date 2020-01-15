<?php

namespace SunValley\LoopUtil\FileLogger;

use DateTimeZone;
use Monolog\Logger;
use React\EventLoop\LoopInterface;
use SunValley\LoopUtil\FileLogger\Monolog\StdIOHandler;

/**
 * Class StdIOLogger,  a shortcut to create a logger with StdIO handler.
 *
 * @package SunValley\LoopUtil\FileLogger
 */
class StdIOLogger extends Logger
{

    public function __construct(string $name, LoopInterface $loop, $level = Logger::DEBUG)
    {
        parent::__construct($name, [], [], null);

        $this->pushHandler(new StdIOHandler($loop, $level));
    }

}