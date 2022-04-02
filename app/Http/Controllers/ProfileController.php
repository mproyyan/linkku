<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function getUserProfile(User $user)
    {
        return response([
            'user' => $user
        ], 200);
    }
}
