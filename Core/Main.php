<?php

declare(strict_types=1);

namespace Core;

use Core\Database\MyDB;
use Core\Queue\Exceptions\NoMessageException;
use Core\Queue\IQueue;
use Core\Utils\ProxyRequest;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Monolog\Logger;
use Throwable;

class Main
{
    /**
     * @var  IQueue
     */
    private IQueue $queue;

    /**
     * @var  Logger
     */
    private Logger $logger;

    /**
     * @var  int
     */
    private int $sleepSec;

    /**
     * @var  ProxyRequest
     */
    private ProxyRequest $proxyRequest;

    /**
     * @throws  BindingResolutionException
     */
    public function __construct()
    {
        $container = App::getContainer();
        $this->queue = $container->make('IQueue');
        $this->logger = $container->make('Logger');
        $this->proxyRequest = $container->make('ProxyRequest');
        $this->sleepSec = intval($_ENV['WAIT_SEC']);
    }

    /**
     * Main function of the parsing process.
     * The method continuously polls the queue for messages every time from env WAIT_SEC and processes them until the queue is empty.
     * The method uses a TaskFactory object to create a Task object from the message type and processes the task.
     * If an exception is thrown during the processing of a task, the method handles it according to the exception type:
     * If a NoMessageException is thrown, the method waits 10 seconds and then polls the queue again.
     * If any other exception is thrown, the method logs the exception message and updates the task's status in the database.
     * If the task has already been tried 5 times, the status is set to "finished_with_error".
     * Otherwise, the status is set to "error" and the number of tries is incremented by one.
     * @return  void
     * @throws  NoMessageException
     * @throws  Throwable
     */
    public function start(): void
    {
        $this->logger->info('Started');
        while (!$this->queue->isEmpty()) {
            $queuedTask = null;
            try {
                $queuedTask = $this->queue->receiveMessage();
                $this->logger->info('Task received');
                $class = $queuedTask->data->type;
                $task = new $class();
                $task->process($queuedTask);
                $this->queue->deleteMessage($queuedTask->id);
                $this->logger->info('Task deleted');
            } catch (NoMessageException) {
                sleep(10);
            } catch (Throwable  $exc) {
                $this->logger->error($exc->getMessage());
                if ($queuedTask !== null) {
                    $this->queue->returnMessageToQueue($queuedTask);
                }else{
                    throw $exc;
                }
            }
            sleep($this->sleepSec);
        }
        $this->logger->info('Queue is empty');
        MyDB::close();
    }

    /**
     * Every 10 minutes recheck proxy from the file and fetches from the API
     * @return  void
     * @throws  GuzzleException
     */

    public function proxyWatcher(): void
    {
        while (!$this->queue->isEmpty()) {
            $this->logger->info('Starting proxy reloading');
            $this->proxyRequest->recheckProxy();
            $this->proxyRequest->reloadProxies();
            $this->logger->info('Finished proxy reloading');
            sleep(300);
        }
    }
}
