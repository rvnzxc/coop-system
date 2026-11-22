<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\Services\ReportExporter;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reports,
        private ReportExporter $exporter
    ) {}

    public function credits(Request $request)
    {
        return $this->export($request, 'credits');
    }

    public function sales(Request $request)
    {
        return $this->export($request, 'sales');
    }

    public function inventory(Request $request)
    {
        return $this->export($request, 'inventory');
    }

    public function memberPurchases(Request $request)
    {
        return $this->export($request, 'member-purchases');
    }

    public function transactions(Request $request)
    {
        return $this->export($request, 'transactions');
    }

    private function export(Request $request, string $type)
    {
        $filters = $this->reports->validate($request, $type);
        $user = Auth::user();
        $cashierId = $user && ! $user->isAdmin() ? (int) $user->id : null;
        $definition = $this->reports->build($type, $filters, $cashierId);
        $filename = $definition['meta']['filename'];

        $response = $filters['format'] === 'csv'
            ? $this->exporter->csv($definition, $filename)
            : $this->exporter->pdf($definition, $filename);

        AuditLogger::log(AuditLog::ACTION_REPORT_EXPORTED, 'report', null, [
            'username' => $user?->username,
            'cashier_id' => $cashierId,
            'report' => $type,
            'format' => $filters['format'],
            'scope' => $filters['scope'],
            'period' => $filters['scope'] === 'period' ? $filters['period'] : null,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
        ]);

        return $response;
    }
}
