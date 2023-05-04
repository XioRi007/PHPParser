<?php

namespace Core\Parser\Tasks\Question;

use Core\Models\Answer;
use Core\Models\Question;
use Core\Models\Task;
use Core\Parser\Tasks\BaseTask;

class QuestionItemTask extends BaseTask
{
    public function process(Task $task): void
    {
        $this->logger->info("started QuestionItemTask for $task->id");
        $document = $this->getDocument($task->id);

        $questionText = $document->first('#HeaderString')->text();
        $question = new Question();
        $question = $question->getOrCreate($questionText);

        $links =  $document->find('.Answer>a');
        $answerList = $this->extractTextFromList($links);
        $links =  $document->find('td[class=Length]');
        $answerLengthList = $this->extractTextFromList($links);

        $combinedArray = array_combine($answerList, $answerLengthList);
        $this->logger->debug("QuestionItemTask found " . count($answerLengthList) . " answers for $questionText question");
        $answer = new Answer();
        foreach ($combinedArray as $answerText => $length) {
            $answer = $answer->getOrCreate($answerText, $length);
            $hasAnswer = $question->hasAnswer($answer);
            if(!$hasAnswer) {
                $question->answers()->attach($answer);
            }
        }
    }
}
