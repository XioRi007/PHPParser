<?php

namespace Core\Queue;

class QueuedTask
{
    /**
     * @var  string
     */
    public string $id;

    /**
     * @var  object
     */
    public object $data;

    public function __construct(string $id, object $data)
    {
        $this->id = $id;
        $this->data = $data;
    }
}
