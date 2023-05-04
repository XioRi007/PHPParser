<?php

namespace Core\Parser\Tasks\Question;

use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;

class QuestionLettersTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started QuestionLettersTask for $task->id");
        $links = $this->getList($task->id);
        $hrefs = $this->extractHrefFromList($links, $task->id);
        $this->logger->debug("QuestionLettersTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Question\QuestionPagesTask']);
        }
    }
}
