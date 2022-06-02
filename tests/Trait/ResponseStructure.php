<?php

namespace Tests\Trait;

trait ResponseStructure
{
   private array $standardApiProblemStructure = [
      'status',
      'type',
      'title',
      'detail'
   ];
}
