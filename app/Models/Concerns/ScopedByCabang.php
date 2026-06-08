<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopedByCabang
{
    public function scopeForCurrentUser(Builder $query): Builder
    {
        $user = auth()->user();
        if (! $user) {
            return $query;
        }
        if (strtolower($user->role?->nama_role ?? '') === 'superadmin') {
            return $query;
        }
        return $query->where('cabang_id', $user->cabang_id);
    }
}