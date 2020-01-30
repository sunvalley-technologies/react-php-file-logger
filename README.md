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

$logFile = __DIR__ . '/test.log';
// expect log file like test-1999-12-31.log 
$logger->pushHandler(new FileHandler($loop, $logFile));
$logger->info('Message!!!');
$loop->run();
```


### Note

Since version 2, this library removed react/filesystem support and `RotatingFileHandler` for simplicity and ordered writes. The old version should still work fine though performance wise keeping some child processes around for some logs might not be that desirable.

Since version 2, this library opens the file with `n` (`O_NONBLOCK`) and handles the file with a writable stream. This probably does not work on Windows and might even be not really that non-blocking open but the stream itself will be non-blocking.
It also by default opens a log file with a date prefix (which can be disabled) and listens for a SIGHUP signal to close and reopen the file stream. This simply replaces a "rotating log file". Use `logrotate` for better rotating of files.
Since the log file stream will be opened once, this should not actually be a problem for a blocking problem on Windows as well.

   
