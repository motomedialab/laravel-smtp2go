<?php

namespace Motomedialab\Smtp2Go\Exceptions;

use Exception;
use Throwable;

class Smtp2GoException extends Exception
{
    protected array $context = [];

    public function context(): array
    {
        return $this->context;
    }

    public function setContext(array $context): static
    {
        $this->context = $context;
        return $this;
    }

    public static function make(string $message, int $code = 0, ?Throwable $previous = null): Smtp2GoException
    {
        return new static($message, $code, $previous);
    }
}
