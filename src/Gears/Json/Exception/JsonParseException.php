<?php

namespace Cosmologist\Gears\Json\Exception;

use RuntimeException;
use Throwable;

class JsonParseException extends RuntimeException
{
    public function __construct(Throwable $previous = null)
    {
        $message = 'Unable to parse response as JSON';

        if (function_exists('json_last_error_msg')) {
            $jsonMessage = json_last_error_msg();
            if ($jsonMessage !== false && $jsonMessage !== 'No error') {
                $message .= ': ' . $jsonMessage;
            }
        }

        parent::__construct($message, json_last_error(), $previous);
    }
}