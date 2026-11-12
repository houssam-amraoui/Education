<?php

namespace App\Support;

enum Ability: string
{
    case EnterAdministration = 'admin.enter';
    case ManageUsers = 'users.manage';
    case ManageRoles = 'roles.manage';
    case AssignRoles = 'roles.assign';
}
