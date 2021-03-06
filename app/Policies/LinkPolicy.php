<?php

namespace App\Policies;

use App\Models\Link;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LinkPolicy
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
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(?User $user, Link $link)
    {
        $user = auth('sanctum')->user();
        return $link->visibility == Link::PUBLIC || $link->user_id == optional($user)->id
            ? Response::allow()
            : Response::deny('You cannot access private link that are not yours');
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
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(?User $user, Link $link)
    {
        $user = auth('sanctum')->user();
        return $link->user_id == optional($user)->id
            ? Response::allow()
            : Response::deny('You cannot update link that are not yours');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(?User $user, Link $link)
    {
        $user = auth('sanctum')->user();
        return $link->user_id == optional($user)->id
            ? Response::allow()
            : Response::deny('You cannot delete link that are not yours');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Link $link)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Link  $link
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Link $link)
    {
        //
    }

    public function visit(?User $user, Link $link)
    {
        $user = auth('sanctum')->user();
        return $link->visibility == Link::PUBLIC || optional($user)->id == $link->user_id
            ? Response::allow()
            : Response::deny('You cannot access private link that are not yours');
    }
}
