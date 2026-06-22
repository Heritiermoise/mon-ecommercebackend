<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ImageService
{
    /**
     * Convertir une image uploadée en URL chiffrée
     * 
     * @param UploadedFile $file
     * @param string $directory
     * @return string URL chiffrée
     */
    public static function uploadAndEncrypt($file, string $directory = 'produits'): string
    {
        try {
            // Générer un nom unique
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Stocker le fichier
            $path = $file->storeAs($directory, $filename, 'public');
            
            // Générer l'URL publique
            $url = Storage::disk('public')->url($path);
            
            // Convertir en URL HTTPS si en local
            if (str_starts_with($url, 'http://')) {
                $url = str_replace('http://', 'https://', $url);
            }
            
            // Chiffrer l'URL avant de la retourner
            $encryptedUrl = Crypt::encryptString($url);
            
            Log::info('Image uploadée et chiffrée', [
                'original' => $file->getClientOriginalName(),
                'path' => $path,
                'url_length' => strlen($url),
            ]);
            
            return $encryptedUrl;
        } catch (\Exception $e) {
            Log::error('Erreur upload image: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Déchiffrer une URL d'image
     * 
     * @param string $encryptedUrl
     * @return string URL déchiffrée
     */
    public static function decryptImageUrl(string $encryptedUrl): string
    {
        if (empty($encryptedUrl)) {
            return '';
        }
        
        try {
            return Crypt::decryptString($encryptedUrl);
        } catch (\Exception $e) {
            // Si ce n'est pas chiffré, retourner tel quel
            return $encryptedUrl;
        }
    }

    /**
     * Supprimer une image
     */
    public static function deleteImage(string $encryptedUrl): bool
    {
        try {
            $url = self::decryptImageUrl($encryptedUrl);
            
            // Extraire le chemin du storage
            $path = str_replace(url('storage'), '', $url);
            $path = ltrim($path, '/');
            
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
     * Convertir une image en base64 (alternative)
     */
    public static function convertToBase64(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $content = file_get_contents($file->getRealPath());
        $base64 = base64_encode($content);
        
        return "data:{$mimeType};base64,{$base64}";
    }

    /**
     * Télécharger une image depuis une URL externe
     */
    public static function downloadFromUrl(string $url, string $directory = 'produits'): string
    {
        try {
            $content = file_get_contents($url);
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = Str::uuid() . '.' . $extension;
            
            Storage::disk('public')->put("{$directory}/{$filename}", $content);
            
            $storedUrl = Storage::disk('public')->url("{$directory}/{$filename}");
            
            return Crypt::encryptString($storedUrl);
        } catch (\Exception $e) {
            Log::error('Erreur téléchargement image: ' . $e->getMessage());
            throw $e;
        }
    }
}