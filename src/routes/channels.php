<?php

use Illuminate\Support\Facades\Broadcast;

if (config('instagram.broadcast.channel_type') === 'private') {

    Broadcast::channel('instagram-messages', function ($user) {
        return $user !== null;
    });

}
