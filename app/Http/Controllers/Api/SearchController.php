<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\Categorie;
use App\Models\Marque;
use App\Models\Tag;
use App\Models\Couleur;
use App\Models\Taille;
use App\Models\RechercheRecente;
use App\Models\ProduitVue;
use App\Models\ProduitAchetes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $terme = trim($request->get('q', ''));
            $categorieId = $request->get('categorie_id');
            $marqueId = $request->get('marque_id');
            $tags = $request->get('tags');
            $couleurs = $request->get('couleurs');
            $tailles = $request->get('tailles');
            $prixMin = $request->get('prix_min');
            $prixMax = $request->get('prix_max');
            $noteMin = $request->get('note_min');
            $enPromo = $request->get('en_promo');
            $enStock = $request->get('en_stock');
            $tri = $request->get('tri', 'pertinence');
            $page = max(1, (int) $request->get('page', 1));
            $perPage = min(48, max(12, (int) $request->get('per_page', 12)));

            $query = Produit::where('statut', 'actif');

            if ($terme) {
                $query->where(function($q) use ($terme) {
                    $q->where('nom', 'like', '%' . $terme . '%')
                      ->orWhere('description', 'like', '%' . $terme . '%')
                      ->orWhere('slug', 'like', '%' . $terme . '%')
                      ->orWhereHas('categorie', function($c) use ($terme) {
                          $c->where('nom', 'like', '%' . $terme . '%');
                      })
                      ->orWhereHas('marque', function($m) use ($terme) {
                          $m->where('nom', 'like', '%' . $terme . '%');
                      })
                      ->orWhereHas('tags', function($t) use ($terme) {
                          $t->where('nom', 'like', '%' . $terme . '%');
                      });
                });
            }

            if ($categorieId) {
                if (is_array($categorieId)) {
                    $query->whereIn('categorie_id', $categorieId);
                } else {
                    $query->where('categorie_id', $categorieId);
                }
            }

            if ($marqueId) {
                if (is_array($marqueId)) {
                    $query->whereIn('marque_id', $marqueId);
                } else {
                    $query->where('marque_id', $marqueId);
                }
            }

            if ($tags && is_array($tags)) {
                $query->whereHas('tags', function($q) use ($tags) {
                    $q->whereIn('tags.id', $tags);
                });
            }

            if ($couleurs && is_array($couleurs)) {
                $query->whereHas('couleurs', function($q) use ($couleurs) {
                    $q->whereIn('couleurs.id', $couleurs);
                });
            }

            if ($tailles && is_array($tailles)) {
                $query->whereHas('tailles', function($q) use ($tailles) {
                    $q->whereIn('tailles.id', $tailles);
                });
            }

            if ($prixMin !== null && $prixMin !== '') {
                $query->where('prix', '>=', (float) $prixMin);
            }

            if ($prixMax !== null && $prixMax !== '') {
                $query->where('prix', '<=', (float) $prixMax);
            }

            if ($noteMin !== null && $noteMin !== '') {
                $query->where('note_moyenne', '>=', (float) $noteMin);
            }

            if ($enPromo === '1' || $enPromo === true) {
                $query->whereNotNull('prix_remise')
                      ->where('prix_remise', '>', 0)
                      ->whereRaw('prix_remise < prix');
            }

            if ($enStock === '1' || $enStock === true) {
                $query->where('quantite_stock', '>', 0);
            }

            switch ($tri) {
                case 'prix_asc':
                    $query->orderBy('prix', 'asc');
                    break;
                case 'prix_desc':
                    $query->orderBy('prix', 'desc');
                    break;
                case 'note':
                    $query->orderByDesc('note_moyenne')->orderByDesc('nombre_avis');
                    break;
                case 'nouveautes':
                    $query->orderByDesc('created_at');
                    break;
                case 'popularite':
                    $query->orderByDesc('nombre_avis')->orderByDesc('note_moyenne');
                    break;
                case 'promo':
                    $query->whereNotNull('prix_remise')
                          ->orderByRaw('(prix - prix_remise) DESC');
                    break;
                default:
                    if ($terme) {
                        $query->orderByRaw("CASE WHEN nom LIKE ? THEN 0 ELSE 1 END", ['%' . $terme . '%'])
                              ->orderByDesc('note_moyenne');
                    } else {
                        $query->orderByDesc('note_moyenne')->orderByDesc('created_at');
                    }
            }

            $produits = $query->with(['categorie', 'marque', 'imagePrincipale', 'tags', 'couleurs'])
                ->paginate($perPage, ['*'], 'page', $page);

            $total = $produits->total();

            if ($terme) {
                RechercheRecente::enregistrer(
                    $terme,
                    $total,
                    Auth::id(),
                    $request->session() ? $request->session()->getId() : null,
                    $request->ip()
                );
            }

            $facettes = $this->calculerFacettes($terme, $categorieId, $marqueId);

            return response()->json([
                'success' => true,
                'data' => [
                    'produits' => $produits->getCollection()->map(function($p) {
                        return $this->formaterProduit($p);
                    })->values(),
                    'pagination' => [
                        'total' => $total,
                        'per_page' => $produits->perPage(),
                        'current_page' => $produits->currentPage(),
                        'last_page' => $produits->lastPage(),
                        'from' => $produits->firstItem(),
                        'to' => $produits->lastItem(),
                    ],
                    'facettes' => $facettes,
                    'terme' => $terme,
                    'filtres_actifs' => [
                        'categorie_id' => $categorieId,
                        'marque_id' => $marqueId,
                        'tags' => $tags,
                        'couleurs' => $couleurs,
                        'tailles' => $tailles,
                        'prix_min' => $prixMin,
                        'prix_max' => $prixMax,
                        'note_min' => $noteMin,
                        'en_promo' => $enPromo,
                        'en_stock' => $enStock,
                        'tri' => $tri,
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('SearchController@search: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function autocomplete(Request $request)
    {
        try {
            $terme = trim($request->get('q', ''));
            
            if (strlen($terme) < 2) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $produits = Produit::where('statut', 'actif')
                ->where('nom', 'like', '%' . $terme . '%')
                ->with(['categorie', 'imagePrincipale'])
                ->limit(5)
                ->get();

            $categories = Categorie::where('nom', 'like', '%' . $terme . '%')->limit(3)->get();
            $marques = Marque::where('nom', 'like', '%' . $terme . '%')->limit(3)->get();
            $suggestions = RechercheRecente::getSuggestions($terme, 5);

            return response()->json([
                'success' => true,
                'data' => [
                    'produits' => $produits->map(function($p) {
                        return [
                            'type' => 'produit',
                            'id' => $p->id,
                            'nom' => $p->nom,
                            'slug' => $p->slug,
                            'categorie' => $p->categorie ? $p->categorie->nom : null,
                            'prix' => (float) ($p->prix_remise ?? $p->prix),
                            'image' => $p->imagePrincipale ? $p->imagePrincipale->url_image : null,
                        ];
                    }),
                    'categories' => $categories->map(function($c) {
                        return ['type' => 'categorie', 'id' => $c->id, 'nom' => $c->nom, 'slug' => $c->slug];
                    }),
                    'marques' => $marques->map(function($m) {
                        return ['type' => 'marque', 'id' => $m->id, 'nom' => $m->nom];
                    }),
                    'suggestions' => $suggestions,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function historique(Request $request)
    {
        try {
            $userId = Auth::id();
            $sessionId = $request->session() ? $request->session()->getId() : null;

            $query = RechercheRecente::query();
            
            if ($userId) {
                $query->where('utilisateur_id', $userId);
            } elseif ($sessionId) {
                $query->where('session_id', $sessionId);
            }

            $historique = $query->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->unique('terme')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $historique->map(function($r) {
                    return [
                        'terme' => $r->terme,
                        'nb_resultats' => $r->nb_resultats,
                        'date' => $r->created_at->format('d/m/Y H:i'),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function supprimerHistorique(Request $request)
    {
        try {
            $userId = Auth::id();
            $sessionId = $request->session() ? $request->session()->getId() : null;

            $query = RechercheRecente::query();
            
            if ($userId) {
                $query->where('utilisateur_id', $userId);
            } elseif ($sessionId) {
                $query->where('session_id', $sessionId);
            }

            $query->delete();

            return response()->json(['success' => true, 'message' => 'Historique supprime']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function tendances()
    {
        try {
            $tendances = RechercheRecente::select('terme', DB::raw('COUNT(*) as nb_recherches'), DB::raw('SUM(nb_resultats) as total_resultats'))
                ->where('created_at', '>', now()->subDays(7))
                ->groupBy('terme')
                ->orderByDesc('nb_recherches')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tendances,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function recemmentVus(Request $request)
    {
        try {
            $userId = Auth::id();
            $sessionId = $request->session() ? $request->session()->getId() : null;

            $produits = ProduitVue::getRecemmentVus($userId, $sessionId, 10);

            return response()->json([
                'success' => true,
                'data' => $produits->map(function($p) {
                    return $this->formaterProduit($p);
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function souventAchetesEnsemble($produitId)
    {
        try {
            $ids = ProduitAchetes::souventAchetesEnsemble($produitId, 4);
            
            $produits = Produit::whereIn('id', $ids)
                ->where('statut', 'actif')
                ->with(['categorie', 'marque', 'imagePrincipale'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $produits->map(function($p) {
                    return $this->formaterProduit($p);
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function enregistrerVue(Request $request, $produitId)
    {
        try {
            ProduitVue::enregistrer(
                $produitId,
                Auth::id(),
                $request->session() ? $request->session()->getId() : null,
                $request->ip()
            );
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function calculerFacettes($terme, $categorieId, $marqueId)
    {
        $baseQuery = Produit::where('statut', 'actif');

        if ($terme) {
            $baseQuery->where(function($q) use ($terme) {
                $q->where('nom', 'like', '%' . $terme . '%')
                  ->orWhere('description', 'like', '%' . $terme . '%');
            });
        }

        if ($categorieId && !is_array($categorieId)) {
            $baseQuery->where('categorie_id', $categorieId);
        }

        if ($marqueId && !is_array($marqueId)) {
            $baseQuery->where('marque_id', $marqueId);
        }

        $categories = (clone $baseQuery)->select('categorie_id', DB::raw('COUNT(*) as count'))
            ->groupBy('categorie_id')
            ->orderByDesc('count')
            ->get()
            ->map(function($item) {
                $cat = Categorie::find($item->categorie_id);
                return $cat ? ['id' => $cat->id, 'nom' => $cat->nom, 'count' => $item->count] : null;
            })
            ->filter();

        $marques = (clone $baseQuery)->select('marque_id', DB::raw('COUNT(*) as count'))
            ->groupBy('marque_id')
            ->orderByDesc('count')
            ->get()
            ->map(function($item) {
                $marque = Marque::find($item->marque_id);
                return $marque ? ['id' => $marque->id, 'nom' => $marque->nom, 'count' => $item->count] : null;
            })
            ->filter();

        $prixStats = (clone $baseQuery)->select(
            DB::raw('MIN(prix) as min_prix'),
            DB::raw('MAX(prix) as max_prix'),
            DB::raw('AVG(prix) as avg_prix')
        )->first();

        return [
            'categories' => $categories->values(),
            'marques' => $marques->values(),
            'prix' => [
                'min' => (float) ($prixStats->min_prix ?? 0),
                'max' => (float) ($prixStats->max_prix ?? 0),
                'avg' => (float) ($prixStats->avg_prix ?? 0),
            ],
        ];
    }

    private function formaterProduit($p)
    {
        $prix = (float) $p->prix;
        $prixRemise = $p->prix_remise ? (float) $p->prix_remise : null;
        $hasRemise = $prixRemise !== null && $prixRemise > 0 && $prixRemise < $prix;

        return [
            'id' => (int) $p->id,
            'nom' => $p->nom,
            'slug' => $p->slug,
            'description' => $p->description,
            'prix' => $prix,
            'prix_remise' => $prixRemise,
            'en_promo' => $hasRemise,
            'pourcentage_reduction' => $hasRemise ? round((($prix - $prixRemise) / $prix) * 100) : 0,
            'quantite_stock' => (int) $p->quantite_stock,
            'en_stock' => $p->quantite_stock > 0,
            'note_moyenne' => (float) ($p->note_moyenne ?? 0),
            'nombre_avis' => (int) ($p->nombre_avis ?? 0),
            'categorie' => $p->categorie ? [
                'id' => (int) $p->categorie->id,
                'nom' => $p->categorie->nom,
                'slug' => $p->categorie->slug,
            ] : null,
            'marque' => $p->marque ? [
                'id' => (int) $p->marque->id,
                'nom' => $p->marque->nom,
            ] : null,
            'image_principale' => $p->imagePrincipale ? $p->imagePrincipale->url_image : null,
            'tags' => $p->tags ? $p->tags->map(function($t) {
                return ['id' => $t->id, 'nom' => $t->nom, 'slug' => $t->slug];
            }) : [],
            'couleurs' => $p->couleurs ? $p->couleurs->map(function($c) {
                return ['id' => $c->id, 'nom' => $c->nom, 'code_hex' => $c->code_hex];
            }) : [],
        ];
    }
}