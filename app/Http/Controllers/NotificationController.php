<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Return the latest notifications for the authenticated user as JSON.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->take(30)
            ->get()
            ->map(fn($n) => [
                'id'          => $n->id,
                'title'       => $n->title,
                'message'     => $n->message,
                'entity_type' => $n->entity_type,
                'entity_id'   => $n->entity_id,
                'read_at'     => $n->read_at,
                'is_read'     => ! is_null($n->read_at),
                'created_at'  => $n->created_at->diffForHumans(),
            ]);

        $unreadCount = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, Notification $notification)
    {
        abort_if($notification->user_id !== Auth::id(), 403);

        $notification->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark ALL unread notifications as read.
     */
    public function markAllRead(Request $request)
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
    }

    /**
     * Return unread count only (lightweight endpoint for polling).
     */
    public function unreadCount()
    {
        $count = Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}
