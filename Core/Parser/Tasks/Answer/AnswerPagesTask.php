<?php

namespace Core\Parser\Tasks\Answer;

use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;

class AnswerPagesTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started AnswerPagesTask for $task->id");
        $links = $this->getList($task->id);
        $hrefs = $this->extractHrefFromList($links, $task->id);
        $this->logger->debug("AnswerPagesTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Answer\AnswerListTask']);
        }
    }
}
