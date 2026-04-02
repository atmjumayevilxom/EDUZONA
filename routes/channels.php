<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel — anyone can subscribe to session updates
Broadcast::channel('session.{code}', function () {
    return true;
});
