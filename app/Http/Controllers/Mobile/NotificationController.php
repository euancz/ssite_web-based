<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('mobile_user');
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $notifications = $user->notifications()
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()->paginate(min(max((int) $request->query('per_page', 15), 1), 50));

        $items = $notifications->getCollection()->map(fn ($notification) => [
            'id' => $notification->id,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'data' => (array) $notification->data,
        ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function summary(Request $request)
    {
        $user = $request->attributes->get('mobile_user');
        $latest = $user->notifications()->latest()->take(10)->get();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $latest->map(fn ($notification) => [
                'id' => $notification->id,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
                'data' => (array) $notification->data,
            ]),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function readAll(Request $request)
    {
        $user = $request->attributes->get('mobile_user');
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    public function read(Request $request, string $notification)
    {
        $user = $request->attributes->get('mobile_user');
        $record = $user->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return response()->json(['ok' => true, 'data' => (array) $record->data]);
    }

    public function destroy(Request $request, string $notification)
    {
        $user = $request->attributes->get('mobile_user');
        $record = $user->notifications()->whereKey($notification)->firstOrFail();
        $record->delete();

        return response()->json(['ok' => true]);
    }
}
