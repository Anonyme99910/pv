<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $query = Alert::with('panel');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if (!$request->boolean('include_dismissed', false)) {
            $query->active();
        }

        if ($request->boolean('unread_only', false)) {
            $query->unread();
        }

        $alerts = $query->orderByDesc('created_at')
            ->limit($request->get('limit', 50))
            ->get();

        return response()->json($alerts->map(function ($alert) {
            return [
                'id' => $alert->id,
                'type' => $alert->type,
                'title' => $alert->title,
                'message' => $alert->message,
                'panelId' => $alert->panel?->panel_code,
                'isRead' => $alert->is_read,
                'timestamp' => $alert->created_at->toIso8601String(),
            ];
        }));
    }

    public function markAsRead(Alert $alert)
    {
        $alert->update(['is_read' => true]);

        return response()->json(['message' => 'Alert marked as read']);
    }

    public function markAllAsRead()
    {
        Alert::where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'All alerts marked as read']);
    }

    public function dismiss(Alert $alert)
    {
        $alert->update(['is_dismissed' => true]);

        return response()->json(['message' => 'Alert dismissed']);
    }

    public function unreadCount()
    {
        $count = Alert::unread()->active()->count();

        return response()->json(['count' => $count]);
    }
}
