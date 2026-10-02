<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * Menerima data booking dari aplikasi eksternal.
     */
    public function store(Request $request)
    {
        // Validasi data yang masuk
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'area' => 'nullable|string|max:255',
            'registration_date' => 'nullable|date',
            'sales_id' => 'nullable|exists:users,id',
            'package_id' => 'nullable|exists:internet_packages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simpan data pelanggan baru dengan status 'booking'
        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'area' => $request->area,
            'registration_date' => $request->registration_date,
            'sales_id' => $request->sales_id,
            'package_id' => $request->package_id,
            'status' => 'booking',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data booking berhasil ditambahkan',
            'data' => $customer
        ], 201);
    }
}
