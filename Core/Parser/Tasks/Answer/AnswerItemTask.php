<?php

namespace Core\Parser\Tasks\Answer;

use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;

class AnswerItemTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started AnswerItemTask for $task->id");
        $document = $this->getDocument($task->id);
        $links =  $document->find('.QuestionShort>a');
        $hrefs = $this->extractHrefFromList($links, $task->id);
        $this->logger->debug("AnswerItemTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Question\QuestionItemTask']);
        }
    }
}
