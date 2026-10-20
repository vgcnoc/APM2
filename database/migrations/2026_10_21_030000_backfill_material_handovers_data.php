<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill existing return and dispatch transactions into the unified Serah Terima system.
     */
    public function up(): void
    {
        // 1. Backfill all existing return transactions (Instalasi, PGS, PGK, PGJ, Rollout, CBP)
        $returns = DB::table('material_transactions')
            ->where(function ($q) {
                $q->where('transaction_number', 'like', 'RTR-%')
                  ->orWhere('transaction_number', 'like', 'IN-%-RET-%')
                  ->orWhere('purpose', 'like', 'Pengembalian%');
            })
            ->get();

        foreach ($returns as $r) {
            $sourceType = 'manual';
            $purpose = strtolower($r->purpose ?? '');
            $trxNum = strtoupper($r->transaction_number ?? '');

            if (str_contains($purpose, 'instalasi') || str_contains($trxNum, 'EXCESS')) {
                $sourceType = 'instalasi';
            } elseif (str_contains($purpose, 'pgs') || str_contains($trxNum, 'IN-PGS')) {
                $sourceType = 'pgs';
            } elseif (str_contains($purpose, 'pgk') || str_contains($trxNum, 'IN-PGK')) {
                $sourceType = 'pgk';
            } elseif (str_contains($purpose, 'pgj') || str_contains($trxNum, 'IN-PGJ')) {
                $sourceType = 'pgj';
            } elseif (str_contains($purpose, 'cabut perangkat') || str_contains($purpose, 'cbp')) {
                $sourceType = 'cbp';
            } elseif (str_contains($purpose, 'rollout') || str_contains($trxNum, 'IN-WO')) {
                $sourceType = 'rollout_odc_odp';
            }

            // Extract ticket / customer / wo reference from purpose
            $sourceRef = $r->source_reference;
            if (!$sourceRef) {
                if (preg_match('/(PGS-\d+-\d+|PGK-\d+-\d+|PGJ-\d+-\d+|CBP-\d+-\d+|WO-[A-Z]+-\d+-\d+)/i', $r->purpose ?? '', $m)) {
                    $sourceRef = $m[1];
                } elseif (preg_match('/Pelanggan\s+(.+)$/i', $r->purpose ?? '', $m)) {
                    $sourceRef = trim($m[1]);
                }
            }

            $isApproved = ($r->status === 'approved');
            $handoverStatus = $isApproved ? 'received' : 'pending_handover';
            $receivedAt = $isApproved ? ($r->date ?: $r->created_at) : null;

            DB::table('material_transactions')
                ->where('id', $r->id)
                ->update([
                    'request_type' => 'return_handover',
                    'source_type' => $sourceType,
                    'source_reference' => $sourceRef,
                    'handover_status' => $handoverStatus,
                    'received_at' => $receivedAt,
                ]);
        }

        // 2. Also backfill source_type for field dispatch transactions
        $dispatches = DB::table('material_transactions')
            ->where('type', 'out')
            ->where(function ($q) {
                $q->whereNull('source_type')->orWhere('source_type', 'none');
            })
            ->get();

        foreach ($dispatches as $d) {
            $purpose = strtolower($d->purpose ?? '');
            $trxNum = strtoupper($d->transaction_number ?? '');
            $sourceType = null;
            $sourceRef = $d->source_reference;

            if (str_contains($trxNum, 'OUT-INSTALASI') || str_contains($purpose, 'instalasi')) {
                $sourceType = 'instalasi';
            } elseif (str_contains($trxNum, 'OUT-PGS') || str_contains($purpose, 'pgs')) {
                $sourceType = 'pgs';
            } elseif (str_contains($trxNum, 'OUT-PGK') || str_contains($purpose, 'pgk')) {
                $sourceType = 'pgk';
            } elseif (str_contains($trxNum, 'OUT-PGJ') || str_contains($purpose, 'pgj')) {
                $sourceType = 'pgj';
            } elseif (str_contains($trxNum, 'TRX-WO') || str_contains($purpose, 'rollout')) {
                $sourceType = 'rollout_odc_odp';
            }

            if (!$sourceRef) {
                if (preg_match('/(PGS-\d+-\d+|PGK-\d+-\d+|PGJ-\d+-\d+|CBP-\d+-\d+|WO-[A-Z]+-\d+-\d+)/i', $d->purpose ?? '', $m)) {
                    $sourceRef = $m[1];
                }
            }

            if ($sourceType) {
                DB::table('material_transactions')
                    ->where('id', $d->id)
                    ->update([
                        'source_type' => $sourceType,
                        'source_reference' => $sourceRef,
                    ]);
            }
        }
    }

    public function down(): void
    {
    }
};
