<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ProfileController extends Controller
{
    public function getUserProfile(User $user)
    {
        return response([
            'user' => $user
        ], 200);
    }

    public function updateBanner(Request $request)
    {
        $request->validate([
            'banner' => 'required|image|max:2048|mimes:jpg,jpeg,png|dimensions:min_width=800,min_height=500'
        ]);

        $user = $request->user();
        $user->banner ? Storage::disk('public')->delete($user->banner) : true;

        $banner = $request->file('banner');
        $path = $banner->hashName('banner');

        $data = Image::make($banner)
            ->fit(800, 500)
            ->encode('jpg');

        Storage::disk('public')->put($path, $data);

        $user->banner = $path;
        $user->save();

        return response(['user' => $user], 200);
    }
}
