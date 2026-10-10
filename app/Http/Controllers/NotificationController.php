<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = AppNotification::latest()->paginate(30);

        if ($request->boolean('mark_read')) {
            AppNotification::whereNull('read_at')->update(['read_at' => now()]);
        }

        return view('notifications.index', compact('notifications'));
    }

    public function markAllRead()
    {
        $count = AppNotification::whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', $count.' notifikasi ditandai sudah dibaca.');
    }

    public function destroy(AppNotification $notification)
    {
        $notification->delete();

        return back();
    }
}