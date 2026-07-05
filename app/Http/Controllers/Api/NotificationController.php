<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::where('utilisateur_id', Auth::id());

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('non_lues') && $request->non_lues === 'true') {
            $query->where('est_lu', false);
        }

        $notifications = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $notifications->getCollection()->map(fn($n) => [
                'id' => $n->id,
                'titre' => $n->titre,
                'message' => $n->message,
                'type' => $n->type,
                'est_lu' => $n->est_lu,
                'lien' => $n->lien,
                'date' => $n->created_at->diffForHumans(),
            ])->values(),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
            'non_lues_count' => Notification::where('utilisateur_id', Auth::id())
                ->where('est_lu', false)->count()
        ]);
    }

    public function markAsRead($id)
    {
        Notification::where('id', $id)
            ->where('utilisateur_id', Auth::id())
            ->update(['est_lu' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Notification::where('utilisateur_id', Auth::id())
            ->where('est_lu', false)
            ->update(['est_lu' => true]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        Notification::where('id', $id)
            ->where('utilisateur_id', Auth::id())
            ->delete();

        return response()->json(['success' => true]);
    }
}