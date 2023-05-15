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

    public function remake(): void
    {
        foreach (new Iterator\Keyspace($this->client, $this->queueName . ":*", 1) as $tmp) {

            $t = json_decode($this->client->rpop($tmp));
            $this->sendMessage($t->url, get_object_vars($t));
        }
        foreach (new Iterator\Keyspace($this->client, $this->queueName . "_deleted:*", 1) as $tmp) {
            $t = json_decode($this->client->rpop($tmp));
            $this->deleteMessage(json_encode($t));
        }
    }

    public function sendMessage(string $id, array $data): void
    {
        if($this->client->sismember($this->queueName . "_urls", $id) == 1){
            //$this->logger->info("Message $id already exists");
            return;
        }
        $this->client->sadd($this->queueName . "_urls", [$id]);
        $value = json_encode($data);
        $this->client->lpush($this->queueName, [$value]);
    }

    public function receiveMessage(): QueuedTask
    {
        $clearKey = '';
        $res =  $this->client->rpoplpush($this->queueName, $this->hiddenQueueName);
        if($res == "" || !is_string($res)){
            throw new NoMessageException();
        }
        return new QueuedTask($clearKey, json_decode($res));
    }

    public function returnProcessingMessagesToQueue(): void
    {
        while ($this->client->llen($this->hiddenQueueName) > 0) {
            $this->client->rpoplpush($this->hiddenQueueName, $this->queueName);
        }
    }

    public function returnMessageToQueue(QueuedTask $queuedTask): void
    {
        $exists = property_exists($queuedTask->data, 'tries');
        if (!$exists || $queuedTask->data->tries < 10) {
            $this->client->lrem($this->hiddenQueueName, 1, json_encode($queuedTask->data));
            $queuedTask->data->tries = $exists ? $queuedTask->data->tries + 1 : 1;
            $this->client->lpush($this->queueName, [json_encode($queuedTask->data)]);
        } else {
            $this->deleteMessage(json_encode($queuedTask->data));
        }
    }


    public function deleteMessage(string $id): void
    {
        $this->client->lrem($this->hiddenQueueName, 1, $id);
        $this->client->lpush($this->queueName . "_deleted", [$id]);
    }

    public function isEmpty(): bool
    {
        if($this->client->llen($this->queueName) > 0 || $this->client->llen($this->hiddenQueueName) > 0) {
            return false;
        }
        return true;
    }
}
