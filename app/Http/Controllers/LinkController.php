<?php

namespace App\Http\Controllers;

use App\Http\Resources\LinkResource;
use Illuminate\Http\Request;
use App\Models\Link;
use App\Models\Tag;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class LinkController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $links = Link::with(['author', 'type', 'tags'])->paginate(20);
        return LinkResource::collection($links);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $tags = Tag::all()->pluck('id');
        $user = $request->user();

        $data = $request->validate([
            'title' => 'required|max:80',
            'url' => 'required|active_url',
            'tags' => 'required|array|max:5|min:1',
            'tags.*' => ['numeric', Rule::in($tags), 'distinct:ignore_case'],
            'visibility' => ['required', 'numeric', Rule::in(Link::VISIBILITY)],
        ]);

        $data['user_id'] = $user->id;
        $data['hash'] = bin2hex(random_bytes(5));

        if ($request->description) {
            $data['description'] = $request->description;
            $data['excerpt'] = Str::limit(strip_tags($request->description), 200, '...');
        }

        $link = Link::create($data);
        foreach ($data['tags'] as $tag) {
            $link->tags()->attach($tag);
        }

        return response([
            'link' => new LinkResource($link->load(['tags', 'author', 'type']))
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
