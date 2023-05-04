<?php

namespace Core\Queue\Exceptions;

use Exception;

class NoMessageException extends Exception
{
    protected $message = "No message in queue\n";
}
