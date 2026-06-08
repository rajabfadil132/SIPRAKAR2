<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, AppNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function readAll(Request $request)
    {
        AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }
}
