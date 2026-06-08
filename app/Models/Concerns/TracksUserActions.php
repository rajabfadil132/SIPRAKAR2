<?php

namespace App\Models\Concerns;

use App\Models\User;

trait TracksUserActions
{
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function createdByUser()
    {
        return $this->creator();
    }

    public function updatedByUser()
    {
        return $this->updater();
    }

    public function deletedByUser()
    {
        return $this->deleter();
    }
}
