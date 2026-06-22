<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function index()
    {
        try {
            $settings = [];
            
            if (DB::getSchemaBuilder()->hasTable('parametres_site')) {
                $settings = DB::table('parametres_site')
                    ->pluck('valeur', 'cle')
                    ->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('SettingsController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->all();
            
            if (DB::getSchemaBuilder()->hasTable('parametres_site')) {
                foreach ($data as $key => $value) {
                    DB::table('parametres_site')->updateOrInsert(
                        ['cle' => $key],
                        ['valeur' => $value]
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Parametres mis a jour'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}