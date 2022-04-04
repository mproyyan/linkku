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

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if ($request->input('name')) {
            $data = $request->validate(['name' => 'required|max:50|regex:/^[a-zA-Z\s]+$/']);
            $user->name = $data['name'];
        }

        if ($request->input('username')) {
            $data = $request->validate(['username' => 'required|min:5|max:15|regex:/^[a-z0-9_.]+$/|unique:users,username,' . $user->id]);
            $user->username = $data['username'];
        }

        if ($request->hasFile('image')) {
            $request->validate(['image' => 'image|max:2048|mimes:jpg,jpeg,png|dimensions:min_width=200,min_height=200']);

            $user->image ? Storage::disk('public')->delete($user->image) : true;

            $image = $request->file('image');
            $path = $image->hashName('avatar');

            $imgFile = Image::make($image)
                ->fit(200)
                ->encode('jpg');

            Storage::disk('public')->put($path, $imgFile);

            $user->image = $path;
        }

        $user->save();

        return response(['user' => $user], 200);
    }
}
