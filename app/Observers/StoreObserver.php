<?php

namespace App\Observers;

use App\Models\Store;

class StoreObserver
{
    /**
     * Handle the Store "created" event.
     *
     * @param  \App\Models\Store  $store
     * @return void
     */
    public function created(Store $store)
    {
        // Send store application submitted notification
        $store->sendApplicationNotification();
    }

    /**
     * Handle the Store "updated" event.
     *
     * @param  \App\Models\Store  $store
     * @return void
     */
    public function updated(Store $store)
    {
        // Check if status was changed
        if ($store->isDirty('status')) {
            $originalStatus = $store->getOriginal('status');
            $newStatus = $store->status;
            
            // Don't send notification if it's the initial creation (pending status)
            if ($originalStatus !== null && $originalStatus !== $newStatus) {
                switch ($newStatus) {
                    case 'approved':
                        $store->sendStoreApprovedNotification($store);
                        break;
                    case 'rejected':
                        $store->sendStoreRejectedNotification($store);
                        break;
                }
            }
        }
    }

    /**
     * Handle the Store "deleted" event.
     *
     * @param  \App\Models\Store  $store
     * @return void
     */
    public function deleted(Store $store)
    {
        //
    }

    /**
     * Handle the Store "restored" event.
     *
     * @param  \App\Models\Store  $store
     * @return void
     */
    public function restored(Store $store)
    {
        //
    }

    /**
     * Handle the Store "force deleted" event.
     *
     * @param  \App\Models\Store  $store
     * @return void
     */
    public function forceDeleted(Store $store)
    {
        //
    }
}
