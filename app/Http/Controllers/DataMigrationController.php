<?php

namespace App\Http\Controllers;

use App\Services\DataMigrationService;
use App\Models\Area;
use App\Models\InternetPackage;
use App\Models\User;
use App\Models\Olt;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class DataMigrationController extends Controller
{
    protected DataMigrationService $migrationService;

    public function __construct(DataMigrationService $migrationService)
    {
        $this->migrationService = $migrationService;
    }

    /**
     * Tampilan Antarmuka Data Migration Center
     */
    public function index(Request $request): Response
    {
        if (!auth()->user()->can('menu_data_migration') && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses ke Data Migration Center.');
        }

        $categories = DataMigrationService::getCategoryDefinitions();

        // Recent migration logs
        $logs = AuditLog::where('action', 'like', 'Data Migration%')
            ->latest()
            ->limit(20)
            ->get();

        // Master Reference summary
        $references = [
            'areas' => Area::select('id', 'name')->orderBy('name')->get(),
            'sales' => User::select('id', 'name', 'email', 'role', 'phone')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($u) {
                    $u->display_name = $u->name . ($u->role ? " (" . ucfirst($u->role) . ")" : "");
                    return $u;
                }),
            'technicians' => User::select('id', 'name', 'email', 'role')->whereIn('role', ['admin', 'teknisi', 'noc', 'maintenance'])->orderBy('name')->get(),
            'olts' => Olt::select('id', 'name', 'ip_address')->orderBy('name')->get(),
            'odcs' => Odc::select('id', 'name', 'olt_id', 'pon_port', 'splitter_ratio')->orderBy('name')->get(),
            'odps' => Odp::select('id', 'name', 'odc_id', 'area_id', 'total_ports', 'used_ports')->orderBy('name')->get(),
            'employees_count' => \App\Models\Employee::count(),
        ];

        return Inertia::render('Settings/DataMigration/Index', [
            'categories' => $categories,
            'references' => $references,
            'logs' => $logs,
        ]);
    }

    /**
     * Unduh Template CSV Resmi Ber-BOM UTF-8
     */
    public function downloadTemplate(string $category): StreamedResponse
    {
        if (!auth()->user()->can('data_migration_template_download') && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunduh template.');
        }

        $csvContent = $this->migrationService->generateTemplateCsv($category);
        $fileName = "template_migrasi_{$category}_" . date('Ymd') . ".csv";

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Jalankan Validasi & Dry-Run
     */
    public function dryRun(Request $request): JsonResponse
    {
        if (!auth()->user()->can('data_migration_dry_run') && !auth()->user()->hasRole('admin')) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'category' => 'required|string',
            'rows' => 'nullable|array',
            'file' => 'nullable|file|mimes:csv,txt|max:15360', // max 15MB
            'options' => 'nullable|array',
        ]);

        $category = $request->input('category');
        $rows = $request->input('rows', []);
        $options = $request->input('options', []);

        // If file was uploaded directly via multipart form
        if ($request->hasFile('file')) {
            $content = file_get_contents($request->file('file')->getRealPath());
            $rows = $this->migrationService->parseCsv($content);
        }

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada baris data yang ditemukan untuk diproses.',
            ], 422);
        }

        $result = $this->migrationService->dryRun($category, $rows, $options);

        return response()->json([
            'success' => true,
            'category' => $category,
            'summary' => $result['summary'],
            'rows' => $result['rows'],
        ]);
    }

    /**
     * Eksekusi Impor Massal dengan Database Transaction
     */
    public function execute(Request $request): JsonResponse
    {
        if (!auth()->user()->can('data_migration_execute') && !auth()->user()->hasRole('admin')) {
            return response()->json(['error' => 'Akses ditolak untuk eksekusi impor.'], 403);
        }

        $request->validate([
            'category' => 'required|string',
            'rows' => 'required|array',
            'options' => 'nullable|array',
        ]);

        $category = $request->input('category');
        $rows = $request->input('rows');
        $options = $request->input('options', []);

        $result = $this->migrationService->execute($category, $rows, $options);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Unduh Laporan Kesalahan / Log Baris Gagal
     */
    public function downloadErrorReport(Request $request): StreamedResponse
    {
        $rows = $request->input('errors', []);
        $category = $request->input('category', 'data');
        $fileName = "laporan_error_migrasi_{$category}_" . date('YmdHis') . ".csv";

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM

            fputcsv($output, ['Nomor Baris', 'Status', 'Daftar Kesalahan', 'Peringatan', 'Data Mentah']);

            foreach ($rows as $item) {
                fputcsv($output, [
                    $item['row_index'] ?? '-',
                    $item['status'] ?? 'error',
                    implode(' | ', $item['errors'] ?? []),
                    implode(' | ', $item['warnings'] ?? []),
                    json_encode($item['raw'] ?? [], JSON_UNESCAPED_UNICODE),
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
