<?php

namespace App\Http\Controllers;

use App\Models\ProjectInvoice;
use App\Models\ProjectInvoiceItem;
use App\Models\Setting;
use App\Support\NumberHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectInvoiceController extends Controller
{
    /**
     * Pastikan hanya akun Admin, HRD, dan Finance (atau yang memiliki izin menu_project_invoices) yang dapat mengakses
     */
    protected function checkAccess(?string $permission = null): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        $userRole = strtolower(trim((string)($user->role ?? '')));
        $allowedRoles = ['admin', 'super admin', 'superadmin', 'hrd', 'finance'];

        if (in_array($userRole, $allowedRoles)) {
            return;
        }

        if ($permission && method_exists($user, 'can') && $user->can($permission)) {
            return;
        }

        if (method_exists($user, 'can') && $user->can('menu_project_invoices')) {
            return;
        }

        abort(403, 'Akses terbatas. Modul Invoice Proyek hanya dapat diakses oleh akun HRD, Finance, dan Administrator.');
    }

    /**
     * Tampilan Utama Daftar Invoice Proyek
     */
    public function index(Request $request): Response
    {
        $this->checkAccess('menu_project_invoices');

        $query = ProjectInvoice::with(['items', 'creator', 'income']);

        // Search filter
        if ($search = trim((string)$request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('client_pic', 'like', "%{$search}%")
                  ->orWhere('client_phone', 'like', "%{$search}%")
                  ->orWhere('po_spk_number', 'like', "%{$search}%")
                  ->orWhere('project_title', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Scheme filter
        if ($scheme = $request->get('scheme')) {
            if ($scheme !== 'all') {
                $query->where('payment_scheme', $scheme);
            }
        }

        // Date filter
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('issue_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('issue_date', '<=', $dateTo);
        }

        // Stats calculation
        $allInvoices = ProjectInvoice::query();
        $totalProjectsCount = (clone $allInvoices)->where('status', '!=', 'cancelled')->count();
        $totalBilledAmount = (float)(clone $allInvoices)->where('status', '!=', 'cancelled')->sum('total_amount');
        $totalReceivedAmount = (float)(clone $allInvoices)->where('status', '!=', 'cancelled')->sum('paid_amount');
        $totalUnpaidAmount = (float)(clone $allInvoices)->whereIn('status', ['unpaid', 'partial'])->sum('remaining_amount');

        $invoices = $query->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        $defaultBank = Setting::get('bank_info', 'BANK BRI 410101025028534 a/n Deri Gantinayasa');

        return Inertia::render('ProjectInvoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $request->get('search', ''),
                'status' => $request->get('status', 'all'),
                'scheme' => $request->get('scheme', 'all'),
                'date_from' => $request->get('date_from', ''),
                'date_to' => $request->get('date_to', ''),
            ],
            'stats' => [
                'total_projects' => $totalProjectsCount,
                'total_billed' => $totalBilledAmount,
                'total_received' => $totalReceivedAmount,
                'total_unpaid' => $totalUnpaidAmount,
            ],
            'defaultBank' => $defaultBank,
        ]);
    }

    /**
     * Simpan Invoice Proyek Baru
     */
    public function store(Request $request)
    {
        $this->checkAccess('project_invoices_create');

        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_pic' => 'nullable|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'client_email' => 'nullable|email|max:255',
            'client_address' => 'nullable|string|max:500',
            'client_npwp' => 'nullable|string|max:50',
            'po_spk_number' => 'nullable|string|max:100',
            'project_title' => 'required|string|max:255',
            'project_location' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_scheme' => 'nullable|string|in:full,termin_dp,termin_progress,termin_final',
            'discount' => 'nullable|numeric|min:0',
            'tax_ppn_percent' => 'nullable|numeric|min:0|max:100',
            'tax_pph23_percent' => 'nullable|numeric|min:0|max:100',
            'payment_bank_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $issueDate = Carbon::parse($validated['issue_date']);
            $invoiceNumber = ProjectInvoice::generateNextInvoiceNumber($issueDate);

            $invoice = ProjectInvoice::create([
                'invoice_number' => $invoiceNumber,
                'client_name' => $validated['client_name'],
                'client_pic' => $validated['client_pic'] ?? null,
                'client_phone' => $validated['client_phone'] ?? null,
                'client_email' => $validated['client_email'] ?? null,
                'client_address' => $validated['client_address'] ?? null,
                'client_npwp' => $validated['client_npwp'] ?? null,
                'po_spk_number' => $validated['po_spk_number'] ?? null,
                'project_title' => $validated['project_title'],
                'project_location' => $validated['project_location'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'payment_scheme' => $validated['payment_scheme'] ?? 'full',
                'discount' => (float)($validated['discount'] ?? 0),
                'tax_ppn_percent' => (float)($validated['tax_ppn_percent'] ?? 0),
                'tax_pph23_percent' => (float)($validated['tax_pph23_percent'] ?? 0),
                'payment_bank_info' => $validated['payment_bank_info'] ?? Setting::get('bank_info', 'BANK BRI 410101025028534 a/n Deri Gantinayasa'),
                'notes' => $validated['notes'] ?? null,
                'status' => 'unpaid',
                'paid_amount' => 0,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                $qty = (float)$itemData['qty'];
                $price = (float)$itemData['unit_price'];
                $totalPrice = round($qty * $price, 2);

                ProjectInvoiceItem::create([
                    'project_invoice_id' => $invoice->id,
                    'description' => $itemData['description'],
                    'qty' => $qty,
                    'unit' => $itemData['unit'],
                    'unit_price' => $price,
                    'total_price' => $totalPrice,
                    'sort_order' => $index + 1,
                ]);
            }

            // Hitung subtotal, pajak, diskon, dan grand total
            $invoice->recalculateTotals();

            DB::commit();

            return redirect()->back()->with('success', "Invoice Proyek {$invoice->invoice_number} untuk {$invoice->client_name} berhasil dibuat!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membuat Invoice Proyek: ' . $e->getMessage());
        }
    }

    /**
     * Perbarui Invoice Proyek
     */
    public function update(Request $request, $id)
    {
        $this->checkAccess('project_invoices_edit');

        $invoice = ProjectInvoice::findOrFail($id);

        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_pic' => 'nullable|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'client_email' => 'nullable|email|max:255',
            'client_address' => 'nullable|string|max:500',
            'client_npwp' => 'nullable|string|max:50',
            'po_spk_number' => 'nullable|string|max:100',
            'project_title' => 'required|string|max:255',
            'project_location' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_scheme' => 'nullable|string|in:full,termin_dp,termin_progress,termin_final',
            'discount' => 'nullable|numeric|min:0',
            'tax_ppn_percent' => 'nullable|numeric|min:0|max:100',
            'tax_pph23_percent' => 'nullable|numeric|min:0|max:100',
            'payment_bank_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $invoice->update([
                'client_name' => $validated['client_name'],
                'client_pic' => $validated['client_pic'] ?? null,
                'client_phone' => $validated['client_phone'] ?? null,
                'client_email' => $validated['client_email'] ?? null,
                'client_address' => $validated['client_address'] ?? null,
                'client_npwp' => $validated['client_npwp'] ?? null,
                'po_spk_number' => $validated['po_spk_number'] ?? null,
                'project_title' => $validated['project_title'],
                'project_location' => $validated['project_location'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'payment_scheme' => $validated['payment_scheme'] ?? 'full',
                'discount' => (float)($validated['discount'] ?? 0),
                'tax_ppn_percent' => (float)($validated['tax_ppn_percent'] ?? 0),
                'tax_pph23_percent' => (float)($validated['tax_pph23_percent'] ?? 0),
                'payment_bank_info' => $validated['payment_bank_info'] ?? $invoice->payment_bank_info,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            // Hapus items lama dan buat ulang
            $invoice->items()->delete();

            foreach ($validated['items'] as $index => $itemData) {
                $qty = (float)$itemData['qty'];
                $price = (float)$itemData['unit_price'];
                $totalPrice = round($qty * $price, 2);

                ProjectInvoiceItem::create([
                    'project_invoice_id' => $invoice->id,
                    'description' => $itemData['description'],
                    'qty' => $qty,
                    'unit' => $itemData['unit'],
                    'unit_price' => $price,
                    'total_price' => $totalPrice,
                    'sort_order' => $index + 1,
                ]);
            }

            $invoice->recalculateTotals();

            // Jika sudah ada pembayaran, update kas
            if ($invoice->paid_amount > 0) {
                $invoice->syncWithKasIncome(auth()->id());
            }

            DB::commit();

            return redirect()->back()->with('success', "Invoice Proyek {$invoice->invoice_number} berhasil diperbarui!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui invoice: ' . $e->getMessage());
        }
    }

    /**
     * Catat Pembayaran / Pelunasan Proyek (Otomatis Masuk Buku Kas Umum)
     */
    public function recordPayment(Request $request, $id)
    {
        $this->checkAccess('project_invoices_pay');

        $invoice = ProjectInvoice::findOrFail($id);

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:1',
            'paid_at' => 'required|date',
            'payment_method' => 'required|string|max:50',
            'payment_notes' => 'nullable|string|max:500',
            'auto_sync_kas' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $newPaid = (float)$validated['paid_amount'];
            $invoice->paid_amount = $newPaid;
            $invoice->paid_at = Carbon::parse($validated['paid_at']);
            $invoice->payment_method = $validated['payment_method'];
            $invoice->payment_notes = $validated['payment_notes'] ?? null;

            // Recalculate status & remaining
            $invoice->recalculateTotals();

            // Auto-Sync Kas Umum
            $autoSync = $request->boolean('auto_sync_kas', true);
            if ($autoSync) {
                $invoice->syncWithKasIncome(auth()->id());
            }

            DB::commit();

            $statusText = $invoice->status === 'paid' ? 'LUNAS (100%)' : 'SEBAGIAN / TERMIN DP';
            return redirect()->back()->with('success', "Pembayaran sebesar Rp " . number_format($newPaid, 0, ',', '.') . " berhasil dicatat! Status: {$statusText} dan tercatat di Buku Kas Umum.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan / Hapus Invoice Proyek
     */
    public function destroy($id)
    {
        $this->checkAccess('project_invoices_delete');

        $invoice = ProjectInvoice::findOrFail($id);

        DB::beginTransaction();
        try {
            // Hapus mutasi kas terkait jika ada
            if ($invoice->income_id) {
                $income = \App\Models\Income::find($invoice->income_id);
                if ($income) {
                    $income->delete();
                }
            }

            $invNumber = $invoice->invoice_number;
            $invoice->delete();

            DB::commit();

            return redirect()->back()->with('success', "Invoice Proyek {$invNumber} berhasil dihapus.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus invoice: ' . $e->getMessage());
        }
    }

    /**
     * Cetak Invoice Proyek Format Resmi B2B (Print / PDF)
     */
    public function printInvoice($id)
    {
        $this->checkAccess('project_invoices_print');

        $invoice = ProjectInvoice::with(['items', 'creator'])->findOrFail($id);

        $company = [
            'name' => Setting::get('company_name', config('app.name', 'ISP Provider')),
            'address' => Setting::get('company_address', ''),
            'phone' => Setting::get('company_phone', ''),
            'email' => Setting::get('company_email', ''),
            'logo' => Setting::get('company_logo', null),
            'npwp' => Setting::get('company_npwp', '00.000.000.0-000.000'),
            'bank_info' => $invoice->payment_bank_info ?: Setting::get('bank_info', 'BANK BRI 410101025028534 a/n Deri Gantinayasa'),
        ];

        $terbilangTotal = NumberHelper::terbilangRupiah($invoice->total_amount);

        return view('print.invoice-proyek', compact('invoice', 'company', 'terbilangTotal'));
    }

    /**
     * Cetak Kwitansi Pembayaran Proyek
     */
    public function printReceipt($id)
    {
        $this->checkAccess('project_invoices_print');

        $invoice = ProjectInvoice::with(['creator'])->findOrFail($id);

        $company = [
            'name' => Setting::get('company_name', config('app.name', 'ISP Provider')),
            'address' => Setting::get('company_address', ''),
            'phone' => Setting::get('company_phone', ''),
            'email' => Setting::get('company_email', ''),
            'logo' => Setting::get('company_logo', null),
        ];

        $amountToReceipt = $invoice->paid_amount > 0 ? $invoice->paid_amount : $invoice->total_amount;
        $terbilangNominal = NumberHelper::terbilangRupiah($amountToReceipt);

        return view('print.kwitansi-proyek', compact('invoice', 'company', 'amountToReceipt', 'terbilangNominal'));
    }
}

