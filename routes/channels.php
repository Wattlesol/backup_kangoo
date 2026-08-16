<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('sanad.request.{bookingId}', function ($user, $bookingId) {
    if ($user->hasAnyRole(['admin', 'demo_admin'])) {
        return true;
    }

    $booking = \App\Models\Booking::find($bookingId);
    if (!$booking) {
        return false;
    }

    if ($user->hasRole('provider')) {
        return (int) $booking->provider_id === (int) $user->id;
    }

    if ($user->hasRole('user')) {
        return (int) $booking->customer_id === (int) $user->id;
    }

    if ($user->hasRole('handyman')) {
        if (!empty($user->provider_id) && (int) $booking->provider_id !== (int) $user->provider_id) {
            return false;
        }

        return $booking->handymanAdded()->where('handyman_id', $user->id)->exists()
            || $user->can('booking list');
    }

    return false;
});
