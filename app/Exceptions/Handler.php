<?php

namespace App\Exceptions;

use App\Support\HttpApiErrorFormat;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable($this->handleTooManyHttpRequestException(...));
        $this->renderable($this->handleValidationException(...));
    }

    /**
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory|null
     */
    protected function handleTooManyHttpRequestException(TooManyRequestsHttpException $e, Request $request)
    {
        if ($request->is('api/*')) {
            $retryAfter = $e->getHeaders()['Retry-After'];
            $tooManyRequestProblem = new HttpApiErrorFormat($e->getStatusCode(), [
                'detail' => "You have exceeded the rate limit. Please try again in {$retryAfter} seconds."
            ]);

            return response($tooManyRequestProblem->toArray(), Response::HTTP_TOO_MANY_REQUESTS);
        }
    }

    /**
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory|null
     */
    protected function handleValidationException(ValidationException $e, Request $request)
    {
        $errors = $e->errors();
        $error = $errors[array_key_first($errors)];

        if (count($errors) > 1 || count($error) > 1) {
            $validationProblem = new HttpApiErrorFormat(Response::HTTP_UNPROCESSABLE_ENTITY, [
                'detail' => "There were multiple problems on field that have occurred.",
                'problems' => $errors
            ]);

            return response($validationProblem->toArray(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validationProblem = new HttpApiErrorFormat(Response::HTTP_UNPROCESSABLE_ENTITY, [
            'detail' => $e->getMessage(),
        ]);

        return response($validationProblem->toArray(), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
