<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecurityController extends Controller
{
    public function dashboard()
    {
        try {
            $blockedIps = 0;
            $totalAttempts24h = 0;
            $suspiciousEvents24h = 0;
            $topAttackingIps = [];
            $recentBlocks = [];

            if (DB::getSchemaBuilder()->hasTable('security_blocked_ips')) {
                $blockedIps = DB::table('security_blocked_ips')
                    ->where('blocked_until', '>', now())
                    ->count();

                $recentBlocks = DB::table('security_blocked_ips')
                    ->where('blocked_until', '>', now())
                    ->orderByDesc('blocked_at')
                    ->limit(10)
                    ->get();
            }

            if (DB::getSchemaBuilder()->hasTable('security_logs')) {
                $totalAttempts24h = DB::table('security_logs')
                    ->where('created_at', '>', now()->subDay())
                    ->count();

                $suspiciousEvents24h = DB::table('security_logs')
                    ->where('created_at', '>', now()->subDay())
                    ->whereIn('event_type', ['attack_pattern_detected', 'bot_behavior', 'suspicious_user_agent'])
                    ->count();

                $topAttackingIps = DB::table('security_logs')
                    ->select('ip_address', DB::raw('COUNT(*) as attempts'))
                    ->where('created_at', '>', now()->subDay())
                    ->groupBy('ip_address')
                    ->orderByDesc('attempts')
                    ->limit(10)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'blocked_ips' => $blockedIps,
                    'total_attempts_24h' => $totalAttempts24h,
                    'suspicious_events_24h' => $suspiciousEvents24h,
                    'top_attacking_ips' => $topAttackingIps,
                    'recent_blocks' => $recentBlocks,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('SecurityController@dashboard: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function blockIp(Request $request)
    {
        try {
            $request->validate([
                'ip' => 'required|string',
                'reason' => 'required|string',
                'duration' => 'required|integer|min:1',
            ]);

            if (DB::getSchemaBuilder()->hasTable('security_blocked_ips')) {
                DB::table('security_blocked_ips')->updateOrInsert(
                    ['ip_address' => $request->ip],
                    [
                        'reason' => $request->reason,
                        'blocked_at' => now(),
                        'blocked_until' => now()->addMinutes($request->duration),
                        'attempts' => 0,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'IP bloquee'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function unblockIp($ip)
    {
        try {
            if (DB::getSchemaBuilder()->hasTable('security_blocked_ips')) {
                DB::table('security_blocked_ips')->where('ip_address', $ip)->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'IP debloquee'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function cleanLogs()
    {
        try {
            $deleted = 0;
            if (DB::getSchemaBuilder()->hasTable('security_logs')) {
                $deleted = DB::table('security_logs')
                    ->where('created_at', '<', now()->subDays(30))
                    ->delete();
            }

            return response()->json([
                'success' => true,
                'message' => "$deleted logs supprimes"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}