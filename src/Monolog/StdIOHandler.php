<?php


namespace SunValley\LoopUtil\FileLogger\Monolog;

use Monolog\Logger;
use React\EventLoop\LoopInterface;
use React\Stream\WritableResourceStream;

class StdIOHandler extends StreamHandler
{

    public function __construct(LoopInterface $loop, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct(new WritableResourceStream(STDOUT, $loop), $level, $bubble);
    }
}