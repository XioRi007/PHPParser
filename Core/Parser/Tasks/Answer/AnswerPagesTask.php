<?php

namespace Core\Parser\Tasks\Answer;

use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;
use Core\Parser\Tasks\Types;

class AnswerPagesTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started AnswerPagesTask for $task->id");
        $links = $this->getList($task->id);
        $hrefs = $this->extractHrefFromList($links, $task->id);
        $this->logger->debug("AnswerPagesTask found " . count($hrefs) . " hrefs");
        foreach ($links as $link) {
            $this->queue->sendMessage($link, ['type'=>Types::ANSWER_LIST->name, 'url'=>$link]);
        }
    }
}
