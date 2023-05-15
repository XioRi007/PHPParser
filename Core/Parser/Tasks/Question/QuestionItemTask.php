<?php

namespace Core\Parser\Tasks\Question;

use Core\Models\Answer;
use Core\Models\Question;
use Core\Parser\Tasks\BaseTask;
use Core\Queue\QueuedTask;

class QuestionItemTask extends BaseTask
{
    public function process(QueuedTask $task): void
    {
        $document = $this->getDocument($task->data->url);

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
