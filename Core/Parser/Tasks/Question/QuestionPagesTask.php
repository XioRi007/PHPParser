<?php

namespace Core\Parser\Tasks\Question;

use Core\Parser\Tasks\BaseTask;
use Core\Queue\QueuedTask;

class QuestionPagesTask extends BaseTask
{
    public function process(QueuedTask $task): void
    {
        $this->logger->info("started QuestionPagesTask for {$task->data->url}");
        $links = $this->getList($task->data->url);
        $hrefs = $this->extractHrefFromList($links, $task->data->url);
        $this->logger->debug("QuestionPagesTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Question\QuestionListTask', 'url'=>$link]);
        }
    }
}
