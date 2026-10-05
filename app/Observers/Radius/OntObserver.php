<?php

namespace App\Observers\Radius;

use App\Models\Customer;
use App\Models\Ont;
use App\Services\RadiusService;

/**
 * Kredensial PPPoE / Hotspot pelanggan saat ini tersimpan di tabel ONT,
 * jadi perubahan di ONT harus ikut disinkronkan ke RADIUS.
 */
class OntObserver
{
    protected const WATCHED = ['pppoe_user', 'pppoe_password', 'customer_id'];

    public function __construct(protected RadiusService $radius) {}

    public function saved(Ont $ont): void
    {
        if (!$ont->wasRecentlyCreated && !$ont->wasChanged(self::WATCHED)) {
            return;
        }

        $this->radius->guard(function (RadiusService $radius) use ($ont) {
            // Pada event "saved", getOriginal() masih berisi nilai sebelum update
            $stale = array_filter([$ont->getOriginal('pppoe_user')]);

            $customer = $ont->customer_id ? Customer::with('ont')->find($ont->customer_id) : null;

            if ($customer) {
                $customer->setRelation('ont', $ont);
                $radius->syncCustomer($customer, $stale);
            } else {
                foreach (array_merge($stale, array_filter([$ont->pppoe_user])) as $username) {
                    $radius->removeUser($username);
                }
            }
        });
    }

    public function deleted(Ont $ont): void
    {
        $this->radius->guard(function (RadiusService $radius) use ($ont) {
            foreach (array_filter([$ont->pppoe_user]) as $username) {
                $radius->removeUser($username);
            }
        });
    }
}
