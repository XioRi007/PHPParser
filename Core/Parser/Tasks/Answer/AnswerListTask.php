<?php

namespace Core\Parser\Tasks\Answer;

use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;

class AnswerListTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started AnswerListTask for $task->id");
        $links = $this->getList($task->id, '.AnswerShort>a');
        $hrefs = $this->extractHrefFromList($links, $task->id);
        $this->logger->debug("AnswerListTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Answer\AnswerItemTask']);
        }
    }
}
