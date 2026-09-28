<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Odp;
use App\Models\Ont;

$odps = Odp::all();
$fixedCount = 0;

foreach ($odps as $odp) {
    // Sumber kebenaran untuk port terpakai adalah jumlah ONT yang terhubung
    $actualUsedPorts = Ont::where('odp_id', $odp->id)->count();
    
    if ($odp->used_ports !== $actualUsedPorts) {
        $odp->used_ports = $actualUsedPorts;
        
        // Update status if needed
        if ($odp->used_ports >= $odp->total_ports) {
            $odp->status = 'full';
        } elseif ($odp->status === 'full' && $odp->used_ports < $odp->total_ports) {
            $odp->status = 'active';
        }
        
        $odp->save();
        $fixedCount++;
        echo "Fixed ODP: {$odp->name} (used_ports updated to {$actualUsedPorts})\n";
    }
}

echo "Done. Fixed $fixedCount ODPs.\n";
