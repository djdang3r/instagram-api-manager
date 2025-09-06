<?php

namespace ScriptDevelop\InstagramApiManager\InstagramApi\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected array $details = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        array $details = []
    ) {
        $this->details = $details;
        parent::__construct($message, $code);
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
