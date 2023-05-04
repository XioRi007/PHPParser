<?php

namespace Core\Parser\Tasks\Question;

use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;
use Core\Parser\Tasks\Types;

class QuestionListTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started QuestionListTask for $task->id");
        $links = $this->getList($task->id, '.Question>a');
        $hrefs = $this->extractHrefFromList($links, $task->id);
        $this->logger->debug("AnswerItemTask found " . count($hrefs) . " hrefs");
        foreach ($links as $link) {
            $this->queue->sendMessage($link, ['type'=>Types::QUESTION_ITEM->name, 'url'=>$link]);
        }
    }
}
