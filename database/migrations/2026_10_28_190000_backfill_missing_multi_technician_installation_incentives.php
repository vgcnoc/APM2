<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Customer;
use App\Models\Incentive;
use App\Models\MasterFee;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $masterFeePasang = (float) (MasterFee::where('type', 'Fee Pasang')->where('is_active', true)->value('nominal') ?? 35000);

        // Ambil semua pelanggan aktif yang memiliki jadwal teknisi instalasi
        $customers = Customer::where('status', 'active')
            ->whereHas('technicianSchedules', fn($q) => $q->where('type', 'installation'))
            ->with(['technicianSchedules' => fn($q) => $q->where('type', 'installation')->with('technician')])
            ->get();

        foreach ($customers as $customer) {
            $assignedTechIds = $customer->technicianSchedules
                ->pluck('technician_id')
                ->unique()
                ->filter()
                ->values()
                ->toArray();

            // Jika teknisi lebih dari 1 orang
            if (count($assignedTechIds) > 1) {
                // Cek insentif pasang yang sudah tercatat untuk pelanggan ini
                $existingIncentives = Incentive::where('type', 'auto')
                    ->where('description', 'like', 'Fee Pasang Pelanggan: ' . $customer->name . '%')
                    ->get();

                // Hanya proses jika pelanggan ini memang pernah diaktivasi dan sudah tercatat setidaknya 1 insentif pasang
                if ($existingIncentives->isNotEmpty()) {
                    $existingTechIds = $existingIncentives->pluck('user_id')->toArray();
                    $missingTechIds = array_diff($assignedTechIds, $existingTechIds);

                    foreach ($missingTechIds as $techId) {
                        $techUser = User::find($techId);
                        if (!$techUser) continue;

                        $feeAmount = ($techUser->installation_fee && (float)$techUser->installation_fee > 0)
                            ? (float) $techUser->installation_fee
                            : $masterFeePasang;

                        if ($feeAmount > 0) {
                            $referenceDate = $existingIncentives->first()?->incentive_date ?? $customer->activation_date ?? now();

                            Incentive::create([
                                'user_id' => $techUser->id,
                                'amount' => $feeAmount,
                                'type' => 'auto',
                                'description' => 'Fee Pasang Pelanggan: ' . $customer->name . ($customer->customer_code ? ' (' . $customer->customer_code . ')' : ''),
                                'incentive_date' => $referenceDate,
                                'status' => 'pending',
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
