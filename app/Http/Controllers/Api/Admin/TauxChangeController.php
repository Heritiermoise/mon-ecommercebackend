<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TauxChangeController extends Controller
{
    public function getActif()
    {
        try {
            $taux = 2800;
            
            if (DB::getSchemaBuilder()->hasTable('taux_change')) {
                $tauxActif = DB::table('taux_change')
                    ->where('est_actif', true)
                    ->orderByDesc('date_application')
                    ->first();
                
                if ($tauxActif) {
                    $taux = (float) $tauxActif->taux;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'taux' => $taux,
                    'devise_source' => 'USD',
                    'devise_cible' => 'CDF',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('TauxChangeController@getActif: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        try {
            $taux = [];
            
            if (DB::getSchemaBuilder()->hasTable('taux_change')) {
                $taux = DB::table('taux_change')
                    ->orderByDesc('date_application')
                    ->paginate(20);
            }

            return response()->json([
                'success' => true,
                'data' => $taux,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'taux' => 'required|numeric|min:0.01',
                'note' => 'nullable|string|max:255',
            ]);

            if (DB::getSchemaBuilder()->hasTable('taux_change')) {
                DB::table('taux_change')->where('est_actif', true)->update(['est_actif' => false]);

                DB::table('taux_change')->insert([
                    'devise_source' => 'USD',
                    'devise_cible' => 'CDF',
                    'taux' => $request->taux,
                    'est_actif' => true,
                    'date_application' => now(),
                    'note' => $request->note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Taux mis a jour',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function history()
    {
        try {
            $history = [];
            
            if (DB::getSchemaBuilder()->hasTable('taux_change')) {
                $history = DB::table('taux_change')
                    ->orderByDesc('date_application')
                    ->limit(50)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}