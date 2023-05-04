<?php

namespace Core\Queue;

use Core\Models\Task;
use Core\Queue\Exceptions\NoMessageException;

interface IQueue
{
    /**
     * Creates the queue if it doesn't exist yet.
     * @return  void
     */
    public function createIfNotExists(): void;

    /**
     * Drops the queue if it exists and recreates it.
     * @return  void
     */
    public function reMigrate(): void;

    /**
     * Sends a message to the queue.
     * @param  string  $id
     * @param  array  $data
     * @return  void
     */
    public function sendMessage(string $id, array $data): void;

    /**
     * Receives a message from the queue.
     * @return  Task
     * @throws  NoMessageException
     */
    public function receiveMessage(): Task;

    /**
     * Returns all currently processing messages back to the queue.
     * @return  void
     */
    public function returnProcessingMessagesToQueue(): void;

    /**
     * Deletes a message from the queue.
     * @param  string  $id
     * @return  void
     */
    public function deleteMessage(string $id): void;

    /**
     * Checks if the queue is empty.
     * @return  bool
     */
    public function isEmpty(): bool;
}
