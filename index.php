<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Core\App;
use Core\Database\MyDB;
use Core\Main;

ini_set('display_errors', 1);
error_reporting(E_ALL);
$logger = null;

try {
    if (! function_exists('pcntl_fork')) {
        die('PCNTL functions not available on this PHP installation');
    }
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    App::createContainer();

    $logger = App::getContainer()->make('Logger');

    //you can choose
    //MyDB::migrate();
    //MyDB::reMigrate();
    $queue = App::getContainer()->make('IQueue');

    //you can choose
//    //$queue->createIfNotExists();
//    $queue->reMigrate();

    //clear comments to reload proxy list, but it will take time
//    $proxy = App::getContainer()->make('ProxyRequest');
//    $proxy->reloadProxies();

    //first tasks with pages to parse
    //$queue->sendMessage('https://www.kreuzwort-raetsel.net/uebersicht-zeichen.html', ['type'=>'\Core\Parser\Tasks\Answer\AnswerSymbolsTask', 'url'=>'https://www.kreuzwort-raetsel.net/uebersicht-zeichen.html']);
    //$queue->sendMessage('https://www.kreuzwort-raetsel.net/uebersicht.html', ['type'=>'\Core\Parser\Tasks\Question\QuestionLettersTask', 'url'=>'https://www.kreuzwort-raetsel.net/uebersicht.html']);

    $threadsCount = intval($_ENV['THREADS']);

    $childProcesses = [];

    declare(ticks=1);
    pcntl_signal(SIGTERM, function () {
        MyDB::close();
        exit(0);
    });

    $pid = pcntl_fork();
    if ($pid == -1) {
        throw new Exception('Error during the creation of proxy watcher');
    } elseif ($pid == 0) {
        try {
            $logger->critical("Starting proxy watcher");
            $main = new Main();
            $main->proxyWatcher();
        } catch (Exception $exception) {
            $logger->critical("Error occurred in child process: " . $exception->getMessage());
        }
        exit(0);
    } else {
        $childProcesses[] = $pid;
    }

    for ($i = 0; $i < $threadsCount; $i++) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new Exception('Error during the creation of subprocess');
        } elseif ($pid == 0) {
            try {
                $main = new Main();
                $main->start();
            } catch (Exception $exception) {
                $logger->critical("Error occurred in child process: " . $exception->getMessage());
            }
            exit(0);
        } else {
            $childProcesses[] = $pid;
        }
        sleep(3);
    }

    function handleStopSignal($childProcesses, $queue, $logger): void
    {
        foreach ($childProcesses as $pid) {
            posix_kill($pid, SIGTERM);
        }
        $queue->returnProcessingMessagesToQueue();
        $logger->critical('Parsing is stopped');
    }
    pcntl_signal(SIGTERM, function () use ($childProcesses, $queue, $logger) {
        $logger->critical('Received SIGTERM');
        handleStopSignal($childProcesses, $queue, $logger);
        exit(0);
    });

    pcntl_signal(SIGINT, function () use ($childProcesses, $queue, $logger) {
        $logger->critical('Received SIGINT');
        handleStopSignal($childProcesses, $queue, $logger);
        exit(0);
    });

    while (count($childProcesses) > 0) {
        $pid = pcntl_wait($status);
        if ($pid > 0) {
            $index = array_search($pid, $childProcesses);
            if ($index !== false) {
                unset($childProcesses[$index]);
            }
        }
    }
    $logger->critical('Parsing is finished');

} catch (Throwable $exc) {
    $logger->critical($exc->getMessage());
    $logger->critical('Parsing is stopped due to the error');
}
