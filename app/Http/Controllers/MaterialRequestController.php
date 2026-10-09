<?php

namespace App\Http\Controllers;

use App\Models\MaterialRequest;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\MaterialStock;
use App\Models\Material;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class MaterialRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = MaterialRequest::with(['user', 'area', 'customer', 'items.material', 'approver'])
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->latest();

        if ($request->search) {
            $query->where('request_number', 'like', "%{$request->search}%")
                  ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15)->withQueryString();

        return Inertia::render('MaterialRequests/Index', [
            'requests' => $requests,
            'filters' => request()->all(['search', 'status']),
            'summary' => [
                'pending' => MaterialRequest::where('status', 'pending')->count(),
                'approved' => MaterialRequest::where('status', 'approved')->count(),
                'rejected' => MaterialRequest::where('status', 'rejected')->count(),
            ]
        ]);
    }

    public function approve(MaterialRequest $materialRequest)
    {
        if ($materialRequest->status !== 'pending') {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        DB::transaction(function () use ($materialRequest) {
            // 1. Approve Request
            $materialRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // 2. Create Surat Jalan (MaterialTransaction OUT)
            $transaction = MaterialTransaction::create([
                'transaction_number' => 'SJ-REQ-' . date('YmdHis'),
                'type' => 'out',
                'user_id' => auth()->id(),
                'technician_id' => $materialRequest->user_id,
                'area_id' => $materialRequest->area_id,
                'date' => now(),
                'notes' => 'Persetujuan Request Material: ' . $materialRequest->request_number,
                'status' => 'completed',
            ]);

            // 3. Process items
            foreach ($materialRequest->items as $item) {
                // Potong stok utama
                $material = $item->material;
                $material->decrement('stock', $item->quantity);

                // Buat item transaksi
                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $item->material_id,
                    'quantity' => $item->quantity,
                    'unit' => $material->unit,
                    'price' => $material->price,
                ]);

                // Tambah stok area teknisi
                $areaStock = MaterialStock::firstOrCreate([
                    'material_id' => $item->material_id,
                    'area_id' => $materialRequest->area_id,
                ]);
                $areaStock->increment('stock', $item->quantity);
            }
        });

        return back()->with('success', 'Request disetujui. Material telah dikirim ke Area Teknisi.');
    }

    public function reject(Request $request, MaterialRequest $materialRequest)
    {
        if ($materialRequest->status !== 'pending') {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        $materialRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Request material ditolak.');
    }
}
