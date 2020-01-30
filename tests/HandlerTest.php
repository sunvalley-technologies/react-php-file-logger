<?php

namespace SunValley\LoopUtil\FileLogger\Tests;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\Filesystem\Filesystem;
use SunValley\LoopUtil\FileLogger\Monolog\FileHandler;
use SunValley\LoopUtil\FileLogger\Monolog\RotatingFileHandler;
use SunValley\LoopUtil\FileLogger\Monolog\StdIOHandler;
use SunValley\LoopUtil\FileLogger\Monolog\StreamHandler;

class HandlerTest extends TestCase
{

    public function testBasicStream()
    {
        $loop    = Factory::create();
        $handler = new StdIOHandler($loop);
        $logger  = new Logger('default');
        $logger->pushHandler($handler);
        $logger->info('Hello World');
        $loop->run();

        try {
            new StreamHandler(null);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }

        $handler->close();
    }

    public function testFileStream()
    {
        $loop       = Factory::create();
        $file       = __DIR__ . '/test.log';
        $expectFile = __DIR__ . '/test.' . date('Y-m-d') . '.log';
        $handler    = new FileHandler($loop, $file);
        $logger     = new Logger('default');
        $logger->pushHandler($handler);
        $logger->info('Hello World');
        $loop->addPeriodicTimer(
            0.24,
            function () use ($handler, $loop) {
                if ($handler->getStream() || $handler->getException()) {
                    $loop->stop();
                }
            }
        );
        $loop->run();


        $this->assertNull($handler->getException());
        $this->assertFileExists($expectFile);
    }

}