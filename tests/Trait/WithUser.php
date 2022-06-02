<?php

namespace Tests\Trait;

use App\Models\User;

trait WithUser
{
   /** @var \App\Models\User */
   private $user;

   private function setupUser()
   {
      $this->user = User::factory()->create();
   }
}
