<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Player;

class PlayerPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $this->isAdmin($admin);
    }

    public function view(Admin $admin, Player $player): bool
    {
        return $this->isAdmin($admin);
    }

    protected function isAdmin(Admin $admin): bool
    {
        return $admin->is_active;
    }
}
