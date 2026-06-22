<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecurityAuditController extends Controller
{
    public function dashboard()
    {
        try {
            $report = SecurityService::generateSecurityReport();
            $suspiciousIPs = SecurityService::detectSuspiciousIPs();

            return response()->json([
                'success' => true,
                'data' => [
                    'report' => $report,
                    'suspicious_ips' => $suspiciousIPs,
                    'generated_at' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('SecurityAudit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logs(Request $request)
    {
        try {
            $query = DB::table('security_logs');

            if ($request->has('event_type')) {
                $query->where('event_type', $request->event_type);
            }

            if ($request->has('ip_address')) {
                $query->where('ip_address', $request->ip_address);
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $logs = $query->orderBy('created_at', 'desc')
                ->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cleanup()
    {
        try {
            $days = request('days', 30);
            $deleted = SecurityService::cleanupOldLogs($days);

            return response()->json([
                'success' => true,
                'message' => "$deleted logs supprimes",
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifyIntegrity()
    {
        try {
            $issues = SecurityService::verifyDataIntegrity();

            return response()->json([
                'success' => true,
                'data' => [
                    'issues' => $issues,
                    'has_issues' => count($issues) > 0,
                    'verified_at' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}