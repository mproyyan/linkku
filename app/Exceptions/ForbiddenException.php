<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;
use Phpro\ApiProblem\Http\ForbiddenProblem;

class ForbiddenException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        if ($request->is('api/*')) {
            $message = $this->message;
            $forbiddenProblem = new ForbiddenProblem($message);

            return response($forbiddenProblem->toArray(), Response::HTTP_FORBIDDEN);
        }
    }
}
