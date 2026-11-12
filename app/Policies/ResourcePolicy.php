<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ResourcePolicy extends CurriculumPolicy
{
    public function view(?User $user, Resource $resource): Response
    {
        return Resource::visibleTo($user)
            ->whereKey($resource->getKey())
            ->where('stored_file_id', $resource->stored_file_id)
            ->exists()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
