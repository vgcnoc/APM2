<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseStockController extends Controller
{
    public function index(Request $request)
    {
        $query = Area::with(['stocks.material' => function($q) {
            $q->orderBy('name');
        }]);

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $areas = $query->orderBy('name')->get();

        return Inertia::render('Inventory/Warehouse/Index', [
            'areas' => $areas,
            'filters' => $request->only(['search'])
        ]);
    }
}
