<?php

namespace App\Exceptions;

/**
 * Thrown when a quiz action conflicts with the session's current state
 * (an active session already exists, or the session is already finalized).
 * Carries an optional payload the API layer surfaces under `data`.
 */
class QuizConflictException extends \RuntimeException
{
    public function __construct(string $message, public readonly array $payload = [])
    {
        parent::__construct($message);
    }
}
