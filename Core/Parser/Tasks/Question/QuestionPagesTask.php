<?php

namespace Core\Parser\Tasks\Question;

use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;

class QuestionPagesTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started QuestionPagesTask for $task->id");
        $links = $this->getList($task->id);
        $hrefs = $this->extractHrefFromList($links, $task->id);
        $this->logger->debug("QuestionPagesTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Question\QuestionListTask']);
        }
    }
}
