<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Campaign;

class CampaignPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $this->isAdmin($admin);
    }

    public function view(Admin $admin, Campaign $campaign): bool
    {
        return $this->isAdmin($admin);
    }

    public function create(Admin $admin): bool
    {
        return $this->isAdmin($admin);
    }

    public function update(Admin $admin, Campaign $campaign): bool
    {
        return $this->isAdmin($admin);
    }

    public function delete(Admin $admin, Campaign $campaign): bool
    {
        return $this->isAdmin($admin);
    }

    protected function isAdmin(Admin $admin): bool
    {
        return $admin->is_active;
    }
}
