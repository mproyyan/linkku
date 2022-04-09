<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArchiveResource;
use App\Http\Resources\LinkResource;
use App\Models\Archive;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ArchiveController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $archives = Archive::with(['author', 'type', 'tags'])
            ->where('visibility', '=', 1)
            ->has('links')
            ->paginate(20);

        return ArchiveResource::collection($archives);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|max:60',
            'tags' => 'required|array|max:5|min:1',
            'tags.*' => 'numeric|exists:tags,id|distinct:ignore_case',
            'visibility' => 'required|numeric|exists:visibilities,id'
        ]);

        if ($request->description) {
            $request->validate(['description' => 'required']);
            $data['description'] = $request->description;
            $data['excerpt'] = Str::limit(strip_tags($request->description), 200);
        }

        $data['user_id'] = auth('sanctum')->id();

        $archive = Archive::create($data);
        foreach ($data['tags'] as $tag) {
            $archive->tags()->attach($tag);
        }

        return response([
            'archive' => new ArchiveResource($archive->load(['author', 'type', 'tags']))
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Archive  $archive
     * @return \Illuminate\Http\Response
     */
    public function show(Archive $archive)
    {
        $this->authorize('view', $archive);

        $archive->views = (int) $archive->views + 1;
        $archive->save();

        return response([
            'archive' => new ArchiveResource($archive->load(['tags', 'author', 'type']))
        ], 200);
    }

    /**
     * Get links from archive.
     *
     * @param  \App\Models\Archive  $archive
     * @return \Illuminate\Http\Response
     */
    public function getLinks(Archive $archive)
    {
        $this->authorize('getLinks', $archive);

        $links = $archive->links()
            ->orderBy('archive_link.created_at', 'desc')
            ->limit(10)
            ->get();

        return LinkResource::collection($links->load(['tags', 'author', 'type']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Archive  $archive
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Archive $archive)
    {
        $this->authorize('update', $archive);

        $data = $request->validate([
            'title' => 'required|max:60',
            'tags' => 'required|array|max:5|min:1',
            'tags.*' => 'numeric|exists:tags,id|distinct:ignore_case',
            'visibility' => 'required|numeric|exists:visibilities,id'
        ]);

        if ($request->description) {
            $request->validate(['description' => 'required']);
            $data['description'] = $request->description;
            $data['excerpt'] = Str::limit(strip_tags($request->description), 200);
        }

        DB::transaction(function () use ($data, $archive) {
            DB::table('taggables')
                ->where('taggable_id', $archive->id)
                ->where('taggable_type', 'App\Models\Archive')
                ->delete();

            foreach ($data['tags'] as $tag) {
                $archive->tags()->attach($tag);
            }

            $archive->update($data);
        });

        return response([
            'archive' => new ArchiveResource($archive->load(['tags', 'author', 'type']))
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Archive  $archive
     * @return \Illuminate\Http\Response
     */
    public function destroy(Archive $archive)
    {
        $this->authorize('delete', $archive);

        DB::transaction(function () use ($archive) {
            DB::table('taggables')
                ->where('taggable_id', $archive->id)
                ->where('taggable_type', 'App\Models\Archive')
                ->delete();

            $archive->delete();
        });

        return response([
            'status' => true,
            'message' => 'Archive deleted successfully'
        ], 200);
    }
}
