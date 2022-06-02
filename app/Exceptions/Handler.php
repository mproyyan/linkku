<?php

namespace App\Exceptions;

use App\Support\HttpApiErrorFormat;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
}
