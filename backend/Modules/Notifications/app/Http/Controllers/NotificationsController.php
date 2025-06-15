<?php

namespace Modules\Notifications\Http\Controllers;

use Modules\Core\Http\Controllers\Controller;
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
        $user          = $request->user();
        /** @phpstan-ignore-next-line staticMethod.dynamicCall */
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

        /** @phpstan-ignore-next-line property.notFound */
        $user->unreadNotifications->markAsRead();

        return true;
    }
}
