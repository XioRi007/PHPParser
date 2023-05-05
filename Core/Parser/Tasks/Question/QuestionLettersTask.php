<?php

namespace Core\Parser\Tasks\Question;

use Core\Parser\Tasks\BaseTask;
use Core\Queue\QueuedTask;

class QuestionLettersTask extends BaseTask
{
    public function process(QueuedTask $task): void
    {
        $this->logger->info("started QuestionLettersTask for {$task->data->url}");
        $links = $this->getList($task->data->url);
        $hrefs = $this->extractHrefFromList($links, $task->data->url);
        $this->logger->debug("QuestionLettersTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Question\QuestionPagesTask', 'url'=>$link]);
        }
    }
}
