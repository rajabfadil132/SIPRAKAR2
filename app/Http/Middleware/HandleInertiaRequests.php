<?php

namespace App\Http\Middleware;

use App\Models\AppNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing(['role.permission', 'roleCategory', 'cabang']);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $user?->permissionMap() ?? [],
                'isAdmin' => in_array($user?->roleKey(), ['admin', 'superadmin'], true),
                'isSuperadmin' => $user?->roleKey() === 'superadmin',
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'notifications' => fn () => $this->notifications($request),
        ];
    }

    private function notifications(Request $request): array
    {
        $user = $request->user();
        if (! $user || ! $user->hasPermission('notifications.view')) {
            return ['count' => 0, 'items' => []];
        }

        $limit = $user->roleKey() === 'superadmin' ? 15 : 10;
        $notifications = AppNotification::query()
            ->where('user_id', $user->id)
            ->latest('notified_at')
            ->latest('id')
            ->limit($limit)
            ->get();

        return [
            'count' => AppNotification::query()->where('user_id', $user->id)->whereNull('read_at')->count(),
            'items' => $notifications->map(fn (AppNotification $item) => [
                'notification_id' => $item->id,
                'id' => $item->source_id,
                'type' => $item->type,
                'message' => $item->message,
                'title' => $item->title,
                'code' => $item->code,
                'status' => $item->status,
                'cabang' => $item->cabang,
                'time' => optional($item->notified_at ?? $item->created_at)->toISOString(),
                'href' => $item->href,
                'is_read' => (bool) $item->read_at,
                'progress' => $item->data['progress'] ?? null,
                'target_selesai' => $item->data['target_selesai'] ?? null,
                'whatsapp_href' => $item->data['whatsapp_href'] ?? null,
                'whatsapp_text' => $item->data['whatsapp_text'] ?? null,
                'data' => $item->data ?? [],
            ])->all(),
        ];
    }
}
