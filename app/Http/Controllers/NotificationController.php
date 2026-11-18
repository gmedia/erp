<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DatabaseNotification::where('notifiable_type', Auth::user()->getMorphClass())
            ->where('notifiable_id', Auth::id())
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = DatabaseNotification::where('notifiable_type', Auth::user()->getMorphClass())
            ->where('notifiable_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $this->findForCurrentUser($id);

        if (! $notification) {
            abort(404, 'Notification not found.');
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function readAll(): JsonResponse
    {
        DatabaseNotification::where('notifiable_type', Auth::user()->getMorphClass())
            ->where('notifiable_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    private function findForCurrentUser(string $id): ?DatabaseNotification
    {
        /** @var DatabaseNotification|null $notification */
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_type', Auth::user()->getMorphClass())
            ->where('notifiable_id', Auth::id())
            ->first();

        return $notification;
    }
}
