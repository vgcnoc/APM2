<?php

namespace App\Http\Controllers;

use App\Models\MaterialTransactionItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaterialMutationController extends Controller
{
    public function index(Request $request)
    {
        $query = MaterialTransactionItem::with(['material', 'transaction.user']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('material', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('transaction', function($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        if ($request->has('type') && $request->type != '') {
            $type = $request->type;
            $query->whereHas('transaction', function($q) use ($type) {
                $q->where('type', $type);
            });
        }

        $mutations = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('MaterialMutations/Index', [
            'mutations' => $mutations,
            'filters' => $request->only(['search', 'type']),
        ]);
    }
}
