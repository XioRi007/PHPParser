<?php

namespace Core\Parser\Tasks\Answer;

use Core\Parser\Tasks\BaseTask;
use Core\Queue\QueuedTask;

class AnswerListTask extends BaseTask
{
    public function process(QueuedTask $task): void
    {
        $links = $this->getList($task->data->url, '.AnswerShort>a');
        $hrefs = $this->extractHrefFromList($links, $task->data->url);
        $this->logger->debug("AnswerListTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Answer\AnswerItemTask', 'url'=>$link]);
        }
    }
}
