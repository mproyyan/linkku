<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Phpro\ApiProblem\Http\ConflictProblem;

class ConflictException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, Response::HTTP_CONFLICT);
    }

    public function render(Request $request)
    {
        if ($request->is('api/*')) {
            $message = $this->message;
            $conflictProblem = new ConflictProblem($message);

            return response($conflictProblem->toArray(), Response::HTTP_CONFLICT);
        }
    }
}
