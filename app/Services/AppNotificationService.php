<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Pekerjaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AppNotificationService
{
    public function pekerjaanCreated(Pekerjaan $pekerjaan): void
    {
        $pekerjaan->loadMissing(['cabang:id,nama_cabang', 'petugas:id,name', 'penanggungJawab:id,name']);

        $this->notifyUsers($this->pekerjaanRecipients($pekerjaan), [
            'type' => 'Pekerjaan diajukan',
            'title' => $pekerjaan->nama_pekerjaan,
            'message' => 'Pekerjaan baru dibuat dan perlu dilengkapi/ditangani sesuai cabang atau penugasan.',
            'code' => $pekerjaan->kode_pekerjaan,
            'status' => $pekerjaan->status,
            'href' => route('pekerjaan.show', $pekerjaan, false),
            'cabang' => $pekerjaan->cabang?->nama_cabang,
            'source_type' => 'pekerjaan:created',
            'source_id' => $pekerjaan->id,
            'data' => [
                'progress' => $pekerjaan->progress,
                'target_selesai' => optional($pekerjaan->target_selesai)->format('Y-m-d'),
            ],
        ]);
    }

    public function pekerjaanStatusChanged(Pekerjaan $pekerjaan, ?string $oldStatus, string $newStatus, ?int $actorId = null): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        $pekerjaan->loadMissing(['cabang:id,nama_cabang']);
        $this->notifyUsers($this->pekerjaanRecipients($pekerjaan, $actorId), [
            'type' => $newStatus === 'Selesai' ? 'Pekerjaan selesai' : 'Status pekerjaan diperbarui',
            'title' => $pekerjaan->nama_pekerjaan,
            'message' => "Status pekerjaan berubah dari " . ($oldStatus ?: '-') . " menjadi {$newStatus}.",
            'code' => $pekerjaan->kode_pekerjaan,
            'status' => $newStatus,
            'href' => route('pekerjaan.show', $pekerjaan, false),
            'cabang' => $pekerjaan->cabang?->nama_cabang,
            'source_type' => 'pekerjaan:status',
            'source_id' => $pekerjaan->id,
            'data' => [
                'old_status' => $oldStatus,
                'progress' => $pekerjaan->progress,
                'target_selesai' => optional($pekerjaan->target_selesai)->format('Y-m-d'),
            ],
        ]);
    }

    private function notifyUsers(Collection $users, array $payload): void
    {
        $users
            ->filter(fn (User $user) => (bool) $user->hasPermission('notifications.view'))
            ->unique('id')
            ->each(function (User $user) use ($payload) {
                AppNotification::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'source_type' => $payload['source_type'] ?? 'general',
                        'source_id' => $payload['source_id'] ?? null,
                        'type' => $payload['type'] ?? 'Notifikasi',
                    ],
                    [
                        'title' => $payload['title'] ?? 'Notifikasi',
                        'message' => $payload['message'] ?? null,
                        'code' => $payload['code'] ?? null,
                        'status' => $payload['status'] ?? null,
                        'href' => $payload['href'] ?? null,
                        'cabang' => $payload['cabang'] ?? null,
                        'data' => $payload['data'] ?? [],
                        'notified_at' => now(),
                        'read_at' => null,
                    ]
                );
            });
    }

    private function pekerjaanRecipients(Pekerjaan $pekerjaan, ?int $exceptUserId = null): Collection
    {
        $ids = collect([
            $pekerjaan->created_by,
            $pekerjaan->updated_by,
            $pekerjaan->petugas_id,
            $pekerjaan->penanggung_jawab_id,
        ])->filter()->values();

        $ids = $ids->merge($pekerjaan->petugasTambahan()->whereNotNull('user_id')->pluck('user_id'));

        return User::query()
            ->with('role.permission')
            ->where('status', 'active')
            ->where(function (Builder $query) use ($pekerjaan, $ids) {
                $query->whereIn('id', $ids->all())
                    ->orWhereHas('role', fn ($role) => $role->where('slug', 'superadmin')->where('is_active', true))
                    ->orWhere(function ($admin) use ($pekerjaan) {
                        $admin->where('cabang_id', $pekerjaan->cabang_id)
                            ->whereHas('role', fn ($role) => $role->where('slug', 'admin')->where('is_active', true));
                    });
            })
            ->when($exceptUserId, fn ($query) => $query->where('id', '!=', $exceptUserId))
            ->get();
    }
}
