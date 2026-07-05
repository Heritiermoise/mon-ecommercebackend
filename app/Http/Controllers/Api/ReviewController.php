<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use App\Models\AvisPhoto;
use App\Models\AvisReponse;
use App\Models\AvisSignalement;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    /**
     * Liste des avis d'un produit
     */
    public function index(Request $request, $produitId)
    {
        try {
            $produit = Produit::findOrFail($produitId);
            
            $query = Avis::where('produit_id', $produitId)
                ->where('est_approuve', true)
                ->with(['utilisateur:id,nom', 'photos', 'reponses.utilisateur:id,nom,role']);

            // Filtres
            if ($request->has('note')) {
                $query->where('note', $request->note);
            }

            if ($request->has('verifie')) {
                $query->where('est_verifie', (bool) $request->verifie);
            }

            // Tri
            $sort = $request->get('sort', 'recent');
            switch ($sort) {
                case 'meilleures':
                    $query->orderByDesc('note')->orderByDesc('created_at');
                    break;
                case 'pires':
                    $query->orderBy('note')->orderByDesc('created_at');
                    break;
                case 'utiles':
                    $query->orderByDesc('nb_utile')->orderByDesc('created_at');
                    break;
                default:
                    $query->orderByDesc('created_at');
            }

            $avis = $query->paginate(10);

            // Statistiques
            $noteMoyenne = Avis::where('produit_id', $produitId)
                ->where('est_approuve', true)
                ->avg('note') ?? 0;
            
            $totalAvis = Avis::where('produit_id', $produitId)
                ->where('est_approuve', true)
                ->count();

            $distribution = [];
            for ($i = 1; $i <= 5; $i++) {
                $distribution[$i] = Avis::where('produit_id', $produitId)
                    ->where('note', $i)
                    ->where('est_approuve', true)
                    ->count();
            }

            $avisVerifies = Avis::where('produit_id', $produitId)
                ->where('est_verifie', true)
                ->where('est_approuve', true)
                ->count();

            $avecPhotos = Avis::where('produit_id', $produitId)
                ->where('est_approuve', true)
                ->whereHas('photos')
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'avis' => $avis->getCollection()->map(function($a) {
                        return [
                            'id' => $a->id,
                            'note' => $a->note,
                            'titre' => $a->titre,
                            'commentaire' => $a->commentaire,
                            'est_verifie' => $a->est_verifie,
                            'nb_utile' => $a->nb_utile,
                            'nb_inutile' => $a->nb_inutile,
                            'date_publication' => $a->created_at->format('d/m/Y H:i'),
                            'utilisateur' => $a->utilisateur ? [
                                'id' => $a->utilisateur->id,
                                'nom' => $a->utilisateur->nom,
                                'initiales' => substr($a->utilisateur->nom, 0, 1),
                            ] : null,
                            'photos' => $a->photos->map(function($p) {
                                return [
                                    'id' => $p->id,
                                    'url' => $p->url_image,
                                    'ordre' => $p->ordre,
                                ];
                            }),
                            'reponses' => $a->reponses->map(function($r) {
                                return [
                                    'id' => $r->id,
                                    'contenu' => $r->contenu,
                                    'est_admin' => $r->est_admin,
                                    'date' => $r->created_at->format('d/m/Y H:i'),
                                    'utilisateur' => $r->utilisateur ? [
                                        'nom' => $r->utilisateur->nom,
                                        'role' => $r->utilisateur->role,
                                    ] : null,
                                ];
                            })->values(),
                        ];
                    }),
                    'pagination' => [
                        'total' => $avis->total(),
                        'per_page' => $avis->perPage(),
                        'current_page' => $avis->currentPage(),
                        'last_page' => $avis->lastPage(),
                    ],
                    'statistiques' => [
                        'note_moyenne' => round($noteMoyenne, 1),
                        'total_avis' => $totalAvis,
                        'distribution' => $distribution,
                        'avis_verifies' => $avisVerifies,
                        'avec_photos' => $avecPhotos,
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('ReviewController@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Créer un avis
     */
    public function store(Request $request, $produitId)
    {
        try {
            $request->validate([
                'note' => 'required|integer|min:1|max:5',
                'titre' => 'nullable|string|max:255',
                'commentaire' => 'required|string|min:10|max:2000',
                'photos' => 'nullable|array|max:5',
                'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            $utilisateur = Auth::user();

            if (!$utilisateur) {
                return response()->json(['success' => false, 'message' => 'Non authentifie'], 401);
            }

            // Vérifier si l'utilisateur a déjà laissé un avis
            $existeDeja = Avis::where('produit_id', $produitId)
                ->where('utilisateur_id', $utilisateur->id)
                ->exists();

            if ($existeDeja) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous avez deja laisse un avis pour ce produit'
                ], 400);
            }

            // Vérifier si l'utilisateur a acheté le produit
            $commandeId = DB::table('commandes')
                ->join('articles_commande', 'commandes.id', '=', 'articles_commande.commande_id')
                ->where('commandes.utilisateur_id', $utilisateur->id)
                ->where('articles_commande.produit_id', $produitId)
                ->where('commandes.statut_paiement', 'paye')
                ->select('commandes.id')
                ->first();

            $avis = Avis::create([
                'produit_id' => $produitId,
                'utilisateur_id' => $utilisateur->id,
                'commande_id' => $commandeId ? $commandeId->id : null,
                'note' => $request->note,
                'titre' => $request->titre,
                'commentaire' => $request->commentaire,
                'est_verifie' => $commandeId !== null,
                'est_approuve' => true,
                'date_publication' => now(),
            ]);

            // Upload des photos
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $index => $file) {
                    $chemin = $file->store('avis-photos', 'public');
                    AvisPhoto::create([
                        'avis_id' => $avis->id,
                        'url_image' => url('storage/' . $chemin),
                        'chemin_fichier' => $chemin,
                        'ordre' => $index,
                    ]);
                }
            }

            // Mettre à jour la note moyenne du produit
            $this->mettreAJourNoteMoyenne($produitId);

            return response()->json([
                'success' => true,
                'message' => 'Avis publie avec succes',
                'data' => $avis->load(['utilisateur:id,nom', 'photos'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('ReviewController@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Voter utile
     */
    public function voterUtile(Request $request, $id)
    {
        try {
            $avis = Avis::findOrFail($id);
            $avis->increment('nb_utile');
            return response()->json(['success' => true, 'nb_utile' => $avis->nb_utile]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Voter inutile
     */
    public function voterInutile(Request $request, $id)
    {
        try {
            $avis = Avis::findOrFail($id);
            $avis->increment('nb_inutile');
            return response()->json(['success' => true, 'nb_inutile' => $avis->nb_inutile]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Répondre à un avis
     */
    public function repondre(Request $request, $avisId)
    {
        try {
            $request->validate([
                'contenu' => 'required|string|min:5|max:1000',
            ]);

            $avis = Avis::findOrFail($avisId);
            $utilisateur = Auth::user();

            $reponse = AvisReponse::create([
                'avis_id' => $avisId,
                'utilisateur_id' => $utilisateur->id,
                'contenu' => $request->contenu,
                'est_admin' => in_array($utilisateur->role, ['administrateur', 'super_administrateur']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reponse ajoutee',
                'data' => $reponse->load('utilisateur:id,nom,role')
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Signaler un avis
     */
    public function signaler(Request $request, $avisId)
    {
        try {
            $request->validate([
                'motif' => 'required|string|max:100',
                'details' => 'nullable|string|max:500',
            ]);

            $existe = AvisSignalement::where('avis_id', $avisId)
                ->where('utilisateur_id', Auth::id())
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous avez deja signale cet avis'
                ], 400);
            }

            AvisSignalement::create([
                'avis_id' => $avisId,
                'utilisateur_id' => Auth::id(),
                'motif' => $request->motif,
                'details' => $request->details,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Avis signale avec succes'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour la note moyenne d'un produit
     */
    private function mettreAJourNoteMoyenne($produitId)
    {
        $produit = Produit::find($produitId);
        if ($produit) {
            $moyenne = Avis::where('produit_id', $produitId)
                ->where('est_approuve', true)
                ->avg('note') ?? 0;
            
            $total = Avis::where('produit_id', $produitId)
                ->where('est_approuve', true)
                ->count();
            
            $produit->update([
                'note_moyenne' => $moyenne,
                'nombre_avis' => $total,
            ]);
        }
    }
}