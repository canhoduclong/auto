<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MediaPolicy
{
    public function viewAny(User $user) {  
       return $user->hasPermission('media.index');
    }

    public function view(User $user, Media $media) {
        return $user->hasPermission('media.view');
    }

    public function create(User $user) {
        return $user->hasPermission('media.create');
    }
    
    public function edit(User $user, Media $media) {
        return $user->hasPermission('media.edit');
    } 
    public function update(User $user, Media $media) {
        return $user->hasPermission('media.update');
    }

    public function delete(User $user, Media $media) {
        return $user->hasPermission('media.delete');
    }
}





