<?php

namespace Core\Parser\Tasks\Question;

use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;

class QuestionListTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started QuestionListTask for $task->id");
        $links = $this->getList($task->id, '.Question>a');
        $hrefs = $this->extractHrefFromList($links, $task->id);
        $this->logger->debug("AnswerItemTask found " . count($hrefs) . " hrefs");
        foreach ($hrefs as $link) {
            $this->queue->sendMessage($link, ['type'=>'\Core\Parser\Tasks\Question\QuestionItemTask']);
        }
    }
}
