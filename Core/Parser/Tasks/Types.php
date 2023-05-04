<?php

namespace Core\Parser\Tasks;

/**
 * Enumeration of possible types of parsing tasks.
 */
enum Types
{
    case QUESTION_LETTERS;
    case QUESTION_PAGES;
    case QUESTION_LIST;
    case QUESTION_ITEM;
    case ANSWER_PAGES;
    case ANSWER_LIST;
    case ANSWER_SYMBOLS;
    case ANSWER_ITEM;
}
