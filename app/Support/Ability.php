<?php

namespace App\Support;

enum Ability: string
{
    public const MANAGE_CONVERSATIONS = 'messages.manage';

    case EnterAdministration = 'admin.enter';
    case ManageUsers = 'users.manage';
    case ManageRoles = 'roles.manage';
    case AssignRoles = 'roles.assign';
}
