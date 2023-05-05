<?php

namespace Core\Parser\Tasks\Question;

use Core\Parser\Tasks\BaseTask;
use Core\Queue\QueuedTask;

class QuestionListTask extends BaseTask
{
    public function process(QueuedTask $task): void
    {
        $this->logger->info("started QuestionListTask for {$task->data->url}");
        $links = $this->getList($task->data->url, '.Question>a');
        $hrefs = $this->extractHrefFromList($links, $task->data->url);
        $this->logger->debug("AnswerItemTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Question\QuestionItemTask', 'url'=>$link]);
        }
    }
}
