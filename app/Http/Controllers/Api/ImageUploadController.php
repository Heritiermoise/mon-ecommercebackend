<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageUploadController extends Controller
{
    /**
     * Upload une image et retourne l'URL HTTPS
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'folder' => 'nullable|string|in:products,categories,users',
            ]);

            $file = $request->file('image');
            $folder = $request->input('folder', 'products');

            // Générer un nom unique
            $extension = $file->getClientOriginalExtension();
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) 
                . '-' . time() . '-' . Str::random(8) . '.' . $extension;

            // Stocker le fichier
            $path = $file->storeAs($folder, $filename, 'public');

            // Générer l'URL complète
            $url = asset('storage/' . $path);

            // Convertir en HTTPS si nécessaire
            $url = str_replace('http://', 'https://', $url);
            if (strpos($url, '://') === 0) {
                $url = 'https:' . $url;
            }

            return response()->json([
                'success' => true,
                'message' => 'Image uploadée avec succès',
                'data' => [
                    'url' => $url,
                    'path' => $path,
                    'filename' => $filename,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultiple(Request $request)
    {
        try {
            $request->validate([
                'images' => 'required|array|min:1|max:10',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'folder' => 'nullable|string|in:products,categories,users',
            ]);

            $folder = $request->input('folder', 'products');
            $uploaded = [];

            foreach ($request->file('images') as $file) {
                $extension = $file->getClientOriginalExtension();
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    . '-' . time() . '-' . Str::random(8) . '.' . $extension;

                $path = $file->storeAs($folder, $filename, 'public');
                $url = asset('storage/' . $path);
                $url = str_replace('http://', 'https://', $url);

                $uploaded[] = [
                    'url' => $url,
                    'path' => $path,
                    'filename' => $filename,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => count($uploaded) . ' image(s) uploadée(s)',
                'data' => $uploaded
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une image
     */
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'path' => 'required|string',
            ]);

            if (Storage::disk('public')->exists($request->path)) {
                Storage::disk('public')->delete($request->path);
                return response()->json([
                    'success' => true,
                    'message' => 'Image supprimée'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Image non trouvée'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}