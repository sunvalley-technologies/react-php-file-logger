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
        $loop     = Factory::create();
        $fs       = Filesystem::create($loop);
        $filename = __DIR__ . '/test.log';
        $handler  = new FileHandler($fs->file($filename));
        $logger   = new Logger('default');
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
        $this->assertFileExists($filename);
    }

    public function testRotatingFileStream()
    {
        $loop      = Factory::create();
        $fs        = Filesystem::create($loop);
        $directory = $fs->dir(__DIR__ . '/logs/');
        $filename  = 'test';

        $returnDate = null;
        $dateFormat = null;
        #$handler    = new RotatingFileHandler($directory, $filename);
        /** @var RotatingFileHandler $handler */
        $handler = $this->getMockBuilder(RotatingFileHandler::class)->setMethods(['getDate'])
                        ->setConstructorArgs([$directory, $filename])
                        ->disableOriginalClone()
                        ->disableArgumentCloning()
            // ->disallowMockingUnknownTypes()
                        ->getMock();

        $handler->method('getDate')->willReturnCallback(
            function () use (&$returnDate) {
                if ($returnDate) {
                    return $returnDate;
                }

                return date(RotatingFileHandler::FILE_PER_DAY);
            }
        );
        $handler->reset();
        //$this->assertEquals($returnDate, $handler->getDate());
        $returnDate = '2020-12-12';
        $this->assertEquals($returnDate, $handler->getDate());
        $returnDate = null;

        $logger = new Logger('default');
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
        $this->assertFileExists(
            $directory->getPath() . '/' . $filename . '-' . date($handler->getDateFormat()) . '.log'
        );

        // force rotation
        $tomorrow   = new \DateTimeImmutable('tomorrow');
        $returnDate = ($tomorrow)->format($handler->getDateFormat());
        $handler->setNextRotation(new \DateTimeImmutable('today'));
        $loop->run();

        $logger->info('Hello World');
        $loop->run();

        $this->assertFileExists(
            $directory->getPath() . '/' . $filename . '-' . date(
                $handler->getDateFormat(),
                $tomorrow->getTimestamp()
            ) . '.log'
        );
    }
}