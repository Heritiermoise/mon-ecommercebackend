<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    private $suspiciousPatterns = [
        '/(\.\.\/|\.\.\\\\)/',
        '/(<script|javascript:|onerror=)/i',
        '/(union\s+select|drop\s+table|insert\s+into)/i',
        '/(exec\(|system\(|passthru\()/i',
        '/(\/etc\/passwd|\/proc\/self)/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (!Schema::hasTable('security_blocked_ips') || !Schema::hasTable('security_logs')) {
                return $next($request);
            }

            $ip = $request->ip();
            $userAgent = $request->userAgent() ?? '';
            $path = $request->path();

            if ($this->isIPBlocked($ip)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acces refuse'
                ], 403);
            }

            if ($this->detectAttack($request)) {
                $this->blockIP($ip, 'Attaque detectee');
                return response()->json([
                    'success' => false,
                    'message' => 'Activite suspecte detectee'
                ], 403);
            }

            if ($this->isRateLimited($ip)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trop de requetes'
                ], 429);
            }

            if ($this->isSuspiciousUserAgent($userAgent)) {
                $this->incrementSuspicion($ip);
            }

            $this->logSecurityEvent($ip, $path, $request->method());

        } catch (\Exception $e) {
            Log::error('SecurityMiddleware error: ' . $e->getMessage());
        }

        $response = $next($request);

        try {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
        } catch (\Exception $e) {
        }

        return $response;
    }

    private function isIPBlocked(string $ip): bool
    {
        try {
            return DB::table('security_blocked_ips')
                ->where('ip_address', $ip)
                ->where('blocked_until', '>', now())
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function blockIP(string $ip, string $reason, int $durationMinutes = 60): void
    {
        try {
            $attempts = DB::table('security_blocked_ips')
                ->where('ip_address', $ip)
                ->value('attempts') ?? 0;

            DB::table('security_blocked_ips')->updateOrInsert(
                ['ip_address' => $ip],
                [
                    'reason' => $reason,
                    'blocked_at' => now(),
                    'blocked_until' => now()->addMinutes($durationMinutes),
                    'attempts' => $attempts + 1,
                ]
            );
        } catch (\Exception $e) {
            Log::error('blockIP error: ' . $e->getMessage());
        }
    }

    private function detectAttack(Request $request): bool
    {
        try {
            $input = $request->getContent() . ' ' . $request->getPathInfo();

            foreach ($this->suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $input)) {
                    return true;
                }
            }

            $recentAttempts = DB::table('security_logs')
                ->where('ip_address', $request->ip())
                ->where('created_at', '>', now()->subMinutes(5))
                ->count();

            return $recentAttempts > 100;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isRateLimited(string $ip): bool
    {
        try {
            $recentRequests = DB::table('security_logs')
                ->where('ip_address', $ip)
                ->where('created_at', '>', now()->subMinute())
                ->count();

            return $recentRequests > 60;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousAgents = ['sqlmap', 'nikto', 'nmap', 'masscan', 'dirbuster'];

        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }

        return false;
    }

    private function incrementSuspicion(string $ip): void
    {
        try {
            $suspicionCount = DB::table('security_logs')
                ->where('ip_address', $ip)
                ->where('event_type', 'suspicious')
                ->where('created_at', '>', now()->subHour())
                ->count();

            if ($suspicionCount > 10) {
                $this->blockIP($ip, 'Activite suspecte repetee', 120);
            }
        } catch (\Exception $e) {
        }
    }

    private function logSecurityEvent(string $ip, string $path, string $method): void
    {
        try {
            DB::table('security_logs')->insert([
                'ip_address' => $ip,
                'path' => substr($path, 0, 255),
                'method' => substr($method, 0, 10),
                'event_type' => 'access',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
        }
    }
}