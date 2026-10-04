<?php

namespace App\Observers\Radius;

use App\Models\Customer;
use App\Services\RadiusService;

class CustomerObserver
{
    public function __construct(protected RadiusService $radius) {}

    public function saved(Customer $customer): void
    {
        if (!$customer->wasRecentlyCreated && !$customer->wasChanged(['status', 'package_id'])) {
            return;
        }

        $this->radius->guard(function (RadiusService $radius) use ($customer) {
            $radius->syncCustomer($customer);

            // Putus sesi aktif agar paket baru / isolir langsung berlaku
            if (!$customer->wasRecentlyCreated) {
                foreach (array_keys($radius->customerAccounts($customer)) as $username) {
                    $radius->disconnect($username);
                }
            }
        });
    }

    public function deleting(Customer $customer): void
    {
        $this->radius->guard(function (RadiusService $radius) use ($customer) {
            foreach (array_keys($radius->customerAccounts($customer)) as $username) {
                $radius->removeUser($username);
                $radius->disconnect($username);
            }
        });
    }
}
