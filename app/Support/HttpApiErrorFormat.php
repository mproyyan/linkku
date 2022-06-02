<?php

namespace App\Support;

use Phpro\ApiProblem\Http\HttpApiProblem;

class HttpApiErrorFormat extends HttpApiProblem
{
   public function __construct(int $statusCode, array $data)
   {
      parent::__construct($statusCode, $data);
   }
}
