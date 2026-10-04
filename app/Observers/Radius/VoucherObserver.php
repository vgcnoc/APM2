<?php

namespace App\Observers\Radius;

use App\Models\Voucher;
use App\Services\RadiusService;

/**
 * Catatan: Voucher::insert() / whereIn()->delete() (bulk) tidak memicu observer,
 * jadi controller memanggil RadiusService secara eksplisit untuk operasi bulk.
 */
class VoucherObserver
{
    public function __construct(protected RadiusService $radius) {}

    public function saved(Voucher $voucher): void
    {
        $this->radius->guard(function (RadiusService $radius) use ($voucher) {
            $oldUsername = $voucher->getOriginal('username');
            if ($oldUsername && $oldUsername !== $voucher->username) {
                $radius->removeUser($oldUsername);
            }
            $radius->syncVouchers([$voucher]);
        });
    }

    public function deleted(Voucher $voucher): void
    {
        $this->radius->guard(fn (RadiusService $radius) => $radius->removeUser($voucher->username));
    }
}
