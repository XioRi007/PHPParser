<?php

declare(strict_types=1);

namespace Core\Parser;

use Core\Parser\Tasks\Answer\AnswerItemTask;
use Core\Parser\Tasks\Answer\AnswerListTask;
use Core\Parser\Tasks\Answer\AnswerPagesTask;
use Core\Parser\Tasks\Answer\AnswerSymbolsTask;
use Core\Parser\Tasks\BaseTask;
use Core\Parser\Tasks\Question\QuestionItemTask;
use Core\Parser\Tasks\Question\QuestionLettersTask;
use Core\Parser\Tasks\Question\QuestionListTask;
use Core\Parser\Tasks\Question\QuestionPagesTask;
use Core\Parser\Tasks\Types;
use Exception;

class TaskFactory
{
    /**
     * @throws Exception
     */
    public function createTask($type): BaseTask
    {
        return match ($type) {
            Types::QUESTION_LETTERS->name => new QuestionLettersTask(),
            Types::QUESTION_PAGES->name => new QuestionPagesTask(),
            Types::QUESTION_LIST->name => new QuestionListTask(),
            Types::QUESTION_ITEM->name => new QuestionItemTask(),
            Types::ANSWER_PAGES->name => new AnswerPagesTask(),
            Types::ANSWER_LIST->name => new AnswerListTask(),
            Types::ANSWER_SYMBOLS->name => new AnswerSymbolsTask(),
            Types::ANSWER_ITEM->name => new AnswerItemTask(),
            default => throw new Exception('Wrong type'),
        };
    }
}
