<?php

namespace Core\Parser\Tasks\Answer;

use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;
use Core\Parser\Tasks\Types;

class AnswerSymbolsTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started AnswerSymbolsTask for $task->id");
        $links = $this->getList($task->id);
        $hrefs = $this->extractHrefFromList($links, $task->id);
        $this->logger->debug("AnswerSymbolsTask found " . count($hrefs) . " hrefs");
        foreach ($links as $link) {
            $this->queue->sendMessage($link, ['type'=>Types::ANSWER_PAGES->name, 'url'=>$link]);
        }
    }
}
