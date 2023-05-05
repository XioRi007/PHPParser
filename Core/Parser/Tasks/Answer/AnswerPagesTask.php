<?php

namespace Core\Parser\Tasks\Answer;

use Core\Parser\Tasks\BaseTask;
use Core\Queue\QueuedTask;

class AnswerPagesTask extends BaseTask
{
    public function process(QueuedTask $task): void
    {
        $this->logger->info("started AnswerPagesTask for {$task->data->url}");
        $links = $this->getList($task->data->url);
        $hrefs = $this->extractHrefFromList($links, $task->data->url);
        $this->logger->debug("AnswerPagesTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Answer\AnswerListTask', 'url'=>$link]);
        }
    }
}
