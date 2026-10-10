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

            // 2. Create Surat Jalan (MaterialTransaction IN)
            $transaction = MaterialTransaction::create([
                'transaction_number' => 'SJ-REQ-' . date('YmdHis'),
                'type' => 'in',
                'user_id' => auth()->id(),
                'technician_name' => $materialRequest->user ? $materialRequest->user->name : 'Teknisi',
                'area_id' => $materialRequest->area_id,
                'date' => now(),
                'purpose' => 'Persetujuan Request Material: ' . $materialRequest->request_number,
                'notes' => 'Permintaan tambahan dari teknisi',
                'status' => 'approved',
            ]);

            // 3. Process items
            $totalCost = 0;
            foreach ($materialRequest->items as $item) {
                $material = $item->material;
                
                $areaStock = MaterialStock::firstOrCreate([
                    'material_id' => $item->material_id,
                    'area_id' => $materialRequest->area_id,
                ], [
                    'stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0
                ]);
                
                $stockBefore = $areaStock->stock;
                
                // Convert requested quantity (usually in Roll/Pack) to base unit for stock deduction
                $qty = $item->quantity;
                $itemUnit = strtolower($material->unit ?? '');
                $deduction = $qty;
                
                if (str_contains(strtolower($material->category), 'kabel') && ($itemUnit === 'roll' || $itemUnit === 'rol')) {
                    $deduction = $qty * ($material->meter_per_roll > 0 ? $material->meter_per_roll : 1000);
                } elseif ($material->category === 'Paku Klem' && ($itemUnit === 'pack' || $itemUnit === 'bungkus')) {
                    $deduction = $qty * ($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1);
                } elseif ($material->category === 'Isolasi' && $itemUnit === 'pcs') {
                    $deduction = $qty * ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50);
                }
                
                $material->stock -= $deduction;
                $areaStock->stock += $deduction;
                
                $pricePerUnit = $material->selling_price ?? ($material->price_per_unit ?? 0);
                
                // Calculate unit price based on base unit to match stock
                if (str_contains(strtolower($material->category), 'kabel') && ($itemUnit === 'roll' || $itemUnit === 'rol')) {
                    $pricePerUnit = $pricePerUnit / ($material->meter_per_roll > 0 ? $material->meter_per_roll : 1000);
                } elseif ($material->category === 'Paku Klem' && ($itemUnit === 'pack' || $itemUnit === 'bungkus')) {
                    $pricePerUnit = $pricePerUnit / ($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1);
                } elseif ($material->category === 'Isolasi' && $itemUnit === 'pcs') {
                    $pricePerUnit = $pricePerUnit / ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50);
                }
                
                $totalPrice = $deduction * $pricePerUnit;
                $totalCost += $totalPrice;

                // Buat item transaksi
                MaterialTransactionItem::create([
                    'material_transaction_id' => $transaction->id,
                    'material_id' => $item->material_id,
                    'quantity' => $qty, // Store the requested quantity (e.g. 1 Roll)
                    'unit' => $material->unit ?? 'pcs',
                    'price_per_unit' => $pricePerUnit,
                    'total_price' => $totalPrice,
                    'stock_before' => $stockBefore,
                    'stock_after' => $areaStock->stock,
                    'condition' => 'Layak Pakai'
                ]);
                
                // Recalculate helper columns
                if (str_contains(strtolower($material->category), 'kabel') && $material->meter_per_roll > 0) {
                    $material->total_rolls = $material->stock / $material->meter_per_roll;
                    $areaStock->total_rolls = $areaStock->stock / $material->meter_per_roll;
                }
                if ($material->category === 'Paku Klem' && $material->pcs_per_pack > 0) {
                    $material->total_packs = $material->stock / $material->pcs_per_pack;
                    $areaStock->total_packs = $areaStock->stock / $material->pcs_per_pack;
                }
                if ($material->category === 'Isolasi' && $material->cm_per_pcs > 0) {
                    $material->total_pieces = $material->stock / $material->cm_per_pcs;
                    $areaStock->total_pieces = $areaStock->stock / $material->cm_per_pcs;
                }
                
                $material->save();
                $areaStock->save();
            }
            
            $transaction->update(['total_cost' => $totalCost]);
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
