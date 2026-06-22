<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SecurityService
{
    /**
     * Vérifier si une requête est suspecte
     */
    public static function isSuspiciousRequest(Request $request): bool
    {
        $suspiciousPatterns = [
            '/(\.\.\/|\.\.\\\\)/', // Directory traversal
            '/(<script|javascript:|onerror=)/i', // XSS
            '/(union\s+select|drop\s+table|insert\s+into)/i', // SQL injection
            '/(exec\(|system\(|passthru\(|shell_exec\()/i', // Command injection
            '/(\/etc\/passwd|\/proc\/self|\/windows\/system32)/i', // File inclusion
        ];

        $input = $request->getContent() . ' ' . $request->getPathInfo();

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                Log::warning('Requête suspecte détectée', [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Valider un mot de passe fort
     */
    public static function validateStrongPassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Minimum 8 caractères';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Au moins une majuscule';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Au moins une minuscule';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Au moins un chiffre';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Au moins un caractère spécial';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => 5 - count($errors),
        ];
    }

    /**
     * Générer un token CSRF
     */
    public static function generateCSRFToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Vérifier un token CSRF
     */
    public static function verifyCSRFToken(string $token, string $sessionToken): bool
    {
        return hash_equals($sessionToken, $token);
    }

    /**
     * Sanitize une entrée utilisateur
     */
    public static function sanitizeInput(string $input): string
    {
        // Supprimer les balises HTML
        $input = strip_tags($input);
        
        // Échapper les caractères spéciaux
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        // Supprimer les espaces multiples
        $input = preg_replace('/\s+/', ' ', $input);
        
        return trim($input);
    }

    /**
     * Vérifier si une IP est dans une liste noire
     */
    public static function isIPBlacklisted(string $ip): bool
    {
        $blacklist = [
            '127.0.0.1', // localhost (peut être retiré en production)
        ];

        return in_array($ip, $blacklist);
    }

    /**
     * Logger une activité suspecte
     */
    public static function logSuspiciousActivity(Request $request, string $reason): void
    {
        Log::warning('Activité suspecte', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Vérifier l'intégrité des données
     */
    public static function verifyDataIntegrity(array $data, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', json_encode($data), env('APP_KEY'));
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Générer une signature pour des données
     */
    public static function generateSignature(array $data): string
    {
        return hash_hmac('sha256', json_encode($data), env('APP_KEY'));
    }
}