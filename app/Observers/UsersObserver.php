<?php

namespace App\Observers;


use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UsersObserver
{
    /**
     * Handle the careers "created" event.
     *
     * @param  \App\Models\User $User
     * @return void
     */
    public function creating(User $User)
    {

    }

    /**
     * Handle the careers "updated" event.
     *
     * @param  \App\Models\User $User
     * @return void
     */
    public function Saving(User $User)
    {



    }

    public function updating (User $User){


        }


    /**
     * Handle the careers "deleted" event.
     *
     * @param  \App\Models\User $User
     * @return void
     */
    public function deleted(User $User)
    {
        //
    }

    /**
     * Handle the careers "restored" event.
     *
     * @param  \App\Models\User $User
     * @return void
     */
    public function restored(User $User)
    {
        //
    }

    /**
     * Handle the careers "force deleted" event.
     *
     * @param  \App\Models\User $User
     * @return void
     */
    public function forceDeleted(User $User)
    {
        //
    }
}
