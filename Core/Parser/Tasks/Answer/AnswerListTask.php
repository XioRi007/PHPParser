<?php

namespace Core\Parser\Tasks\Answer;

use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;
use Core\Parser\Tasks\Types;

class AnswerListTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started AnswerListTask for $task->id");
        $links = $this->getList($task->id, '.AnswerShort>a');
        $hrefs = $this->extractHrefFromList($links, $task->id);
        $this->logger->debug("AnswerListTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>Types::ANSWER_ITEM->name]);
        }
    }
}
