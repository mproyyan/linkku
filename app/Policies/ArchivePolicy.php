<?php

namespace App\Policies;

use App\Models\Archive;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArchivePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Archive  $archive
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(?User $user, Archive $archive)
    {
        $user = auth('sanctum')->user();
        return $archive->visibility == Archive::PUBLIC || $archive->user_id == optional($user)->id;
    }

    public function getLinks(?User $user, Archive $archive)
    {
        $user = auth('sanctum')->user();
        return $archive->visibility == Archive::PUBLIC || $archive->user_id == optional($user)->id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Archive  $archive
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(?User $user, Archive $archive)
    {
        $user = auth('sanctum')->user();
        return $archive->user_id == optional($user)->id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Archive  $archive
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(?User $user, Archive $archive)
    {
        $user = auth('sanctum')->user();
        return $archive->user_id == optional($user)->id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Archive  $archive
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Archive $archive)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Archive  $archive
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Archive $archive)
    {
        //
    }

    public function addLink(?User $user, Archive $archive)
    {
        $user = auth('sanctum')->user();
        return optional($user)->id == $archive->user_id;
    }

    public function deleteLink(?User $user, Archive $archive)
    {
        $user = auth('sanctum')->user();
        return optional($user)->id == $archive->user_id;
    }
}
