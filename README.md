# React PHP File Logger

A PSR non-blocking file logger for react php. Uses monolog and provides non blocking monolog handlers.

## Installing

``
composer require sunvalley-technologies/react-php-file-logger
``

## Usage

Convenient loggers that create a quick monolog logger available as `FileLogger`, `RotatingFileLogger` and `StdIOLogger`.

These loggers are just a quick short-cut to use the non blocking handlers specified below.

#### Monolog/StreamHandler

```php
use Monolog\Logger;
use React\Stream\WritableResourceStream;
use SunValley\LoopUtil\FileLogger\Monolog\StreamHandler;

$logger = new Logger('name');
$loop = \React\EventLoop\Factory::create();
$logger->pushHandler(new StreamHandler(new WritableResourceStream(STDOUT, $loop)));
$logger->info('Message!!!');
$loop->run();
```

#### Monolog/StdIOHandler

```php
use Monolog\Logger;
use SunValley\LoopUtil\FileLogger\Monolog\StdIOHandler;

$logger = new Logger('name');
$loop = \React\EventLoop\Factory::create();
$logger->pushHandler(new StdIOHandler($loop));
$logger->info('Message!!!');
$loop->run();
```

#### Monolog/FileHandler

```php
use Monolog\Logger;
use SunValley\LoopUtil\FileLogger\Monolog\FileHandler;

$logger = new Logger('name');
$loop = \React\EventLoop\Factory::create();
$fs = \React\Filesystem\Filesystem::create($loop);

$logFile = __DIR__ . '/test.log';
$logger->pushHandler(new FileHandler($fs->file($logFile)));
$logger->info('Message!!!');
$loop->run();
```

#### Monolog/RotatingFileHandler

```php
use Monolog\Logger;
use SunValley\LoopUtil\FileLogger\Monolog\RotatingFileHandler;

$logger = new Logger('name');
$loop = \React\EventLoop\Factory::create();
$fs = \React\Filesystem\Filesystem::create($loop);

$logDirectory = __DIR__ . '/logs';
$filename ='test'; # ./logs/test-{Y-m-d}.log
$maxFiles = 10;
$logger->pushHandler(new RotatingFileHandler($fs->dir($logDirectory), $filename, 10));
$logger->info('Message!!!');
$loop->run();
```