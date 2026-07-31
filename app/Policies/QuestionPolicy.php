<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Question;

class QuestionPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $this->isAdmin($admin);
    }

    public function view(Admin $admin, Question $question): bool
    {
        return $this->isAdmin($admin);
    }

    public function create(Admin $admin): bool
    {
        return $this->isAdmin($admin);
    }

    public function update(Admin $admin, Question $question): bool
    {
        return $this->isAdmin($admin);
    }

    public function delete(Admin $admin, Question $question): bool
    {
        return $this->isAdmin($admin);
    }

    protected function isAdmin(Admin $admin): bool
    {
        return $admin->is_active;
    }
}
