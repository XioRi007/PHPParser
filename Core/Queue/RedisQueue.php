<?php

namespace Core\Queue;

use Core\Queue\Exceptions\NoMessageException;
use Monolog\Logger;
use Predis\Client;
use Predis\Collection\Iterator;

class RedisQueue implements IQueue
{
    private Client $client;
    private string $queueName;
    private string $hiddenQueueName;
    private Logger $logger;

    public function __construct(Logger $logger, string $queueName = 'parser_queue')
    {
        $this->queueName = $queueName;
        $this->hiddenQueueName = $queueName.'_hidden';
        $this->logger = $logger;
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => $_ENV['REDIS_HOST'],
            'port'   => $_ENV['REDIS_PORT'],
        ]);
    }

    public function __destruct()
    {
        $this->client->disconnect();
    }

    public function createIfNotExists(): void
    {
        //
    }

    public function reMigrate(): void
    {
        foreach (new Iterator\Keyspace($this->client, $this->queueName . "*") as $keys) {
            $this->client->del($keys);
        }
    }

    public function sendMessage(string $id, array $data): void
    {
        $key = "$this->queueName:".md5($id);
        if($this->client->exists($key) || $this->client->exists("$this->hiddenQueueName:".md5($id)) || $this->client->exists("{$this->queueName}_deleted:".md5($id))) {
            $this->logger->info("Message $id already exists");
            return;
        }
        $value = json_encode($data);
        $this->client->lpush($key, [$value]);
    }

    public function receiveMessage(): QueuedTask
    {
	    $cnt = 0;
        $clearKey = '';
        $res = '';
        foreach (new Iterator\Keyspace($this->client, $this->queueName . ":*", 1) as $tmp) {
            if($cnt >= 20)
                throw new NoMessageException();
            $key = $tmp;
            if($key == ""){
                $cnt++;
                continue;
            }
            $clearKey = substr($key, strrpos($key, ':') + 1);
            $dest = $this->hiddenQueueName . ":" . $clearKey;
            $res = $this->client->rpoplpush($key, $dest);
            if($res == "" || !is_string($res)) {
                $cnt++;
            } else {
                break;
            }
	    }
        return new QueuedTask($clearKey, json_decode($res));
    }

    public function returnProcessingMessagesToQueue(): void
    {
        foreach (new Iterator\Keyspace($this->client, $this->hiddenQueueName . ":*", 1) as $task) {
            $clearKey = substr($task, strrpos($task, ':') + 1);
            $source = $this->hiddenQueueName . ":" . $clearKey;
            $dest = $this->queueName . ":" . $clearKey;
            $this->client->rpoplpush($source, $dest);
        }
    }

    public function returnMessageToQueue(QueuedTask $queuedTask): void
    {
        $exists = property_exists($queuedTask->data, 'tries');
        if (!$exists || $queuedTask->data->tries < 10) {
            $source = $this->hiddenQueueName . ":" . $queuedTask->id;
            $queuedTask->data->tries = $exists ? $queuedTask->data->tries + 1 : 1;
            $dest = $this->queueName . ":" . $queuedTask->id;
            $this->client->transaction(function ($tx) use ($source, $dest, $queuedTask) {
                $this->client->del($source);
                $this->client->lpush($dest, [json_encode($queuedTask->data)]);
            });
        } else {
		    $this->deleteMessage($queuedTask->id);
        }
    }


    public function deleteMessage(string $id): void
    {
        $key = $this->hiddenQueueName . ":$id";
        $dest = $this->queueName . "_deleted:" . $id;
        $this->client->rpoplpush($key, $dest);
    }

    public function isEmpty(): bool
    {
        foreach (new Iterator\Keyspace($this->client, $this->queueName . "*", 1) as $tmp) {
            if($tmp) {
                return false;
            }
        }
        return true;
    }
}
