<?php

namespace App\Exceptions;

use App\Support\HttpApiErrorFormat;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Phpro\ApiProblem\Http\ForbiddenProblem;
use Phpro\ApiProblem\Http\NotFoundProblem;
use Phpro\ApiProblem\Http\UnauthorizedProblem;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        $this->renderable($this->handleAuthorizationException(...));
        $this->renderable($this->handleAuthenticationException(...));
        $this->renderable($this->handleNotFoundHttpException(...));
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
        if ($request->is('api/*')) {
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

    /**
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory|null
     */
    protected function handleAuthorizationException(AccessDeniedHttpException $e, Request $request)
    {
        if ($request->is('api/*')) {
            $forbiddenProblem = new ForbiddenProblem($e->getMessage());

            return response($forbiddenProblem->toArray(), $e->getStatusCode());
        }
    }

    /**
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory|null
     */
    protected function handleAuthenticationException(AuthenticationException $e, Request $request)
    {
        if ($request->is('api/*')) {
            $unauthorizedProblem = new UnauthorizedProblem($e->getMessage());

            return response($unauthorizedProblem->toArray(), Response::HTTP_UNAUTHORIZED);
        }
    }

    protected function handleNotFoundHttpException(NotFoundHttpException $e, Request $request)
    {
        if ($request->is('api/*')) {
            $notFoundProblem = new NotFoundProblem($e->getMessage());

            return response($notFoundProblem->toArray(), Response::HTTP_NOT_FOUND);
        }
    }
}
