<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewAdminController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Avis::with(['produit', 'utilisateur', 'photos', 'reponses']);

            if ($request->has('produit_id')) {
                $query->where('produit_id', $request->produit_id);
            }

            if ($request->has('note')) {
                $query->where('note', $request->note);
            }

            if ($request->has('est_approuve')) {
                $query->where('est_approuve', (bool) $request->est_approuve);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('commentaire', 'like', '%' . $search . '%')
                      ->orWhere('titre', 'like', '%' . $search . '%');
                });
            }

            $sort = $request->get('sort', 'recent');
            if ($sort === 'meilleures') {
                $query->orderByDesc('note');
            } elseif ($sort === 'pires') {
                $query->orderBy('note');
            } else {
                $query->orderByDesc('created_at');
            }

            $avis = $query->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $avis,
                'pagination' => [
                    'total' => $avis->total(),
                    'per_page' => $avis->perPage(),
                    'current_page' => $avis->currentPage(),
                    'last_page' => $avis->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('ReviewAdminController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function approuver($id)
    {
        try {
            $avis = Avis::findOrFail($id);
            $avis->update(['est_approuve' => true]);
            return response()->json(['success' => true, 'message' => 'Avis approuve']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function desapprouver($id)
    {
        try {
            $avis = Avis::findOrFail($id);
            $avis->update(['est_approuve' => false]);
            return response()->json(['success' => true, 'message' => 'Avis desapprouve']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $avis = Avis::findOrFail($id);
            $avis->delete();
            return response()->json(['success' => true, 'message' => 'Avis supprime']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function repondre(Request $request, $avisId)
    {
        try {
            $request->validate(['contenu' => 'required|string|min:5']);
            
            $reponse = \App\Models\AvisReponse::create([
                'avis_id' => $avisId,
                'utilisateur_id' => auth()->id(),
                'contenu' => $request->contenu,
                'est_admin' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reponse ajoutee',
                'data' => $reponse
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function signalements()
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('avis_signalements')) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $signalements = DB::table('avis_signalements')
                ->where('est_traite', false)
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $signalements,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function traiterSignalement($id)
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('avis_signalements')) {
                DB::table('avis_signalements')
                    ->where('id', $id)
                    ->update(['est_traite' => true]);
            }
            return response()->json(['success' => true, 'message' => 'Signalement traite']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}