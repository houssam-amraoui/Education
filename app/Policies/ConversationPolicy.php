<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Role;
use App\Models\User;
use App\Support\Ability;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{
    public function create(User $actor): bool
    {
        return $actor->roles()->where('key', Role::STUDENT)->exists();
    }

    public function view(User $actor, Conversation $conversation): Response
    {
        return $this->followUp($actor, $conversation)->allowed() || $this->manage($actor)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function followUp(User $actor, Conversation $conversation): Response
    {
        return $actor->getKey() === $conversation->student_id && $this->create($actor)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function manage(User $actor): bool
    {
        return $actor->canGrantAbilities([Ability::EnterAdministration->value, Ability::MANAGE_CONVERSATIONS])
            && $actor->roles()->whereIn('key', [Role::TEACHER, Role::ADMINISTRATOR])->exists();
    }

    public function reply(User $actor, Conversation $conversation): bool
    {
        return $this->manage($actor) && $actor->getKey() !== $conversation->student_id;
    }
}
