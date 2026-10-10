<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseStockController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Material::with(['stocks.area']);

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
        }

        $materials = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Inventory/Warehouse/Index', [
            'materials' => $materials,
            'filters' => $request->only(['search'])
        ]);
    }
}
