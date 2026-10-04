<?php

namespace App\Observers\Radius;

use App\Models\Voucher;
use App\Models\VoucherProfile;
use App\Services\RadiusService;

class VoucherProfileObserver
{
    public function __construct(protected RadiusService $radius) {}

    public function saved(VoucherProfile $profile): void
    {
        $this->radius->guard(fn (RadiusService $radius) => $radius->syncVoucherProfile($profile));
    }

    public function deleting(VoucherProfile $profile): void
    {
        // Voucher terhapus via cascade DB (tanpa event), jadi bersihkan RADIUS di sini
        $this->radius->guard(function (RadiusService $radius) use ($profile) {
            Voucher::where('voucher_profile_id', $profile->id)
                ->whereNotNull('username')
                ->pluck('username')
                ->each(fn ($username) => $radius->removeUser($username));

            $radius->removeGroup($radius->voucherGroup($profile->id));
        });
    }
}
