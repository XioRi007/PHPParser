<?php

namespace Core\Parser\Tasks\Answer;

use Core\Parser\Tasks\BaseTask;
use Core\Queue\QueuedTask;

class AnswerItemTask extends BaseTask
{
    public function process(QueuedTask $task): void
    {
        $this->logger->info("started AnswerItemTask for {$task->data->url}");
        $document = $this->getDocument($task->data->url);
        $links =  $document->find('.QuestionShort>a');
        $hrefs = $this->extractHrefFromList($links, $task->data->url);
        $this->logger->debug("AnswerItemTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Question\QuestionItemTask', 'url'=>$link]);
        }
    }
}
