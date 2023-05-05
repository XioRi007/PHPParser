<?php

namespace Core;

use Core\Database\MyDB;
use Core\Queue\RedisQueue;
use Core\Utils\ProxyRequest;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class App
{
    /**
     * @var  Container
     */
    protected static Container $container;

    /**
     * Creates container and binds dependencies
     * @return  void
     */
    public static function createContainer(): void
    {
        static::$container = new Container();
        Facade::setFacadeApplication(static::$container);

        $capsule = MyDB::connect();
        static::$container->singleton('db', function () use ($capsule) {
            return $capsule->getConnection();
        });

        $log = new Logger('Logger');
        $formatter = new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n", "Y-m-d, g:i a");

        $commonStream = new StreamHandler('php://stdout', Level::Debug);
        $commonStream->setFormatter($formatter);
        $log->pushHandler($commonStream);

        $fileStream = new StreamHandler('logs.log', Level::Critical);
        $fileStream->setFormatter($formatter);
        $log->pushHandler($fileStream);

        $log->pushProcessor(function ($record) {
            $record->extra['pid'] = getmypid();
            return $record;
        });

        $queue = new RedisQueue($log);
        static::$container->singleton('IQueue', function () use ($queue) {
            return $queue;
        });

        static::$container->singleton('Logger', function () use ($log) {
            return $log;
        });
        static::$container->singleton('ProxyRequest', function () {
            return new ProxyRequest();
        });
    }

    /**
     * @return  Container
     */
    public static function getContainer(): Container
    {
        if (!static::$container) {
            static::createContainer();
        }
        return static::$container;
    }
}
