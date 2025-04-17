<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Notifications\Http\Resources\NotificationResource;

class NotificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function listNotifications(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $notifications = $user->notifications()
            ->limit(15)
            ->orderBy('read_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return NotificationResource::collection($notifications);
    }

    public function markAllAsRead(Request $request): true
    {
        /** @var User $user */
        $user = $request->user();

        $user->unreadNotifications->markAsRead();

        return true;
    }
}
