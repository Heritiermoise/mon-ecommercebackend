<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BotDetectionMiddleware
{
    private $suspiciousUserAgents = [
        'sqlmap', 'nikto', 'nmap', 'masscan', 'dirbuster',
        'burp', 'owasp', 'acunetix', 'nessus', 'openvas',
        'wget', 'curl' // Autoriser uniquement pour API
    ];

    private $suspiciousPatterns = [
        '/(\.\.\/|\.\.\\\\)/', // Directory traversal
        '/(<script|javascript:|onerror=)/i', // XSS
        '/(union\s+select|drop\s+table|insert\s+into|delete\s+from)/i', // SQL injection
        '/(exec\(|system\(|passthru\(|shell_exec\()/i', // Command injection
        '/(\/etc\/passwd|\/proc\/self|\/windows\/system32)/i', // File inclusion
        '/(\%00|\%0d|\%0a)/i', // Null byte injection
    ];

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $ip = $request->ip();
            $userAgent = $request->userAgent() ?? '';
            $path = $request->path();
            $input = $request->all();

            // 1. Vérifier User-Agent suspect
            if ($this->isSuspiciousUserAgent($userAgent)) {
                $this->logSecurityEvent($ip, 'suspicious_user_agent', $userAgent);
                $this->blockIP($ip, 'User-Agent suspect detecte', 30);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Acces refuse'
                ], 403);
            }

            // 2. Détecter patterns d'attaque
            $requestData = json_encode($input) . ' ' . $path . ' ' . $request->getQueryString();
            
            foreach ($this->suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $requestData)) {
                    $this->logSecurityEvent($ip, 'attack_pattern_detected', $pattern);
                    $this->blockIP($ip, 'Pattern d attaque detecte', 60);
                    
                    Log::error("Attaque detectee: IP=$ip, Pattern=$pattern, Path=$path");
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Requete malveillante detectee'
                    ], 403);
                }
            }

            // 3. Vérifier requêtes trop rapides (bot behavior)
            $recentRequests = DB::table('security_logs')
                ->where('ip_address', $ip)
                ->where('created_at', '>', now()->subSeconds(10))
                ->count();

            if ($recentRequests > 20) { // Plus de 20 requêtes en 10 secondes
                $this->logSecurityEvent($ip, 'bot_behavior', "$recentRequests requetes en 10s");
                $this->blockIP($ip, 'Comportement de bot detecte', 15);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Activite suspecte detectee'
                ], 429);
            }

            // 4. Logger la requête
            $this->logSecurityEvent($ip, 'access', $path);

        } catch (\Exception $e) {
            Log::error('BotDetection error: ' . $e->getMessage());
        }

        return $next($request);
    }

    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        foreach ($this->suspiciousUserAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }
        return false;
    }

    private function blockIP(string $ip, string $reason, int $durationMinutes): void
    {
        try {
            DB::table('security_blocked_ips')->updateOrInsert(
                ['ip_address' => $ip],
                [
                    'reason' => $reason,
                    'blocked_at' => now(),
                    'blocked_until' => now()->addMinutes($durationMinutes),
                    'attempts' => DB::table('security_blocked_ips')
                        ->where('ip_address', $ip)
                        ->value('attempts') + 1,
                ]
            );
        } catch (\Exception $e) {
            Log::error('blockIP error: ' . $e->getMessage());
        }
    }

    private function logSecurityEvent(string $ip, string $eventType, string $details = ''): void
    {
        try {
            DB::table('security_logs')->insert([
                'ip_address' => $ip,
                'event_type' => $eventType,
                'details' => substr($details, 0, 255),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silent fail pour éviter boucle infinie
        }
    }
}