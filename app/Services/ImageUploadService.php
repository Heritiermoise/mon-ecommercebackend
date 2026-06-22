<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImageUploadService
{
    public static function upload(UploadedFile $file, string $directory = 'produits'): array
    {
        try {
            self::validateFile($file);

            $extension = strtolower($file->getClientOriginalExtension());
            $filename = Str::uuid() . '.' . $extension;
            $path = $directory . '/' . date('Y/m') . '/' . $filename;

            // Stocker le fichier
            Storage::disk('public')->putFileAs(
                dirname($path),
                $file,
                basename($path)
            );

            // Générer l'URL HTTPS
            $url = self::getPublicUrl($path);

            Log::info('Image uploadée', [
                'original' => $file->getClientOriginalName(),
                'path' => $path,
                'url' => $url,
            ]);

            return [
                'success' => true,
                'url' => $url,
                'path' => $path,
                'filename' => $filename,
            ];
        } catch (\Exception $e) {
            Log::error('Erreur upload image: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public static function delete(string $path): bool
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Erreur suppression image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Générer une URL publique HTTPS (accessible partout)
     */
    public static function getPublicUrl(string $path): string
    {
        // En développement local
        if (app()->environment('local')) {
            return 'http://localhost:8000/storage/' . $path;
        }

        // En production, forcer HTTPS
        $baseUrl = config('app.url');
        $baseUrl = str_replace('http://', 'https://', $baseUrl);
        
        return rtrim($baseUrl, '/') . '/storage/' . $path;
    }

    private static function validateFile(UploadedFile $file): void
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception('Format non supporté. Utilisez: ' . implode(', ', $allowedExtensions));
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('Taille maximale: 5MB');
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Type de fichier non valide');
        }
    }
}