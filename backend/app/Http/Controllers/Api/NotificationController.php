<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // On récupère les notifications non lues et les 5 dernières lues
        $notifications = [
            'unread' => $user->unreadNotifications,
            'read' => $user->readNotifications()->take(5)->get(),
        ];

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'Notification marquée comme lue.']);
        }

        return response()->json(['message' => 'Notification non trouvée.'], 404);
    }
}
