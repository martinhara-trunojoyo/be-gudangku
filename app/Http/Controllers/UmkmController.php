<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Umkm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UmkmController extends Controller
{
    /**
     * Check if user is admin
     */
    private function checkAdminRole()
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Only admin can perform this action.'
            ], 403);
        }
        return null;
    }

    public function store(Request $request)
    {
        // Check admin role
        $roleCheck = $this->checkAdminRole();
        if ($roleCheck) return $roleCheck;

        // Check if admin already has UMKM
        if (Auth::user()->umkm_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already have a UMKM registered'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'nama_umkm' => 'required|string|max:255',
            'pemilik' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'kontak' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create UMKM
            $umkm = Umkm::create([
                'nama_umkm' => $request->nama_umkm,
                'pemilik' => $request->pemilik,
                'alamat' => $request->alamat,
                'kontak' => $request->kontak
            ]);

            // Update user's umkm_id
            $userId = Auth::id();
            DB::table('users')->where('id', $userId)->update(['umkm_id' => $umkm->id]);
            
            // Get updated user data
            $user = User::find($userId);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'UMKM created successfully',
                'data' => [
                    'umkm' => $umkm,
                    'user' => $user
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create UMKM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        // Check admin role
        $roleCheck = $this->checkAdminRole();
        if ($roleCheck) return $roleCheck;

        // Check if admin has UMKM
        if (!Auth::user()->umkm_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have a UMKM to update'
            ], 400);
        }

        // 1. Mencari data
        $umkm = Umkm::find(Auth::user()->umkm_id);

        if (!$umkm) {
            return response()->json([
                'status' => 'error',
                'message' => 'UMKM not found'
            ], 404);
        }

        // 2. Validator
        $validator = Validator::make($request->all(), [
            'nama_umkm' => 'required|string|max:255',
            'pemilik' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'kontak' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 3. Siapkan data yang ingin di update
        $data = [
            'nama_umkm' => $request->nama_umkm,
            'pemilik' => $request->pemilik,
            'alamat' => $request->alamat,
            'kontak' => $request->kontak
        ];

        // 4. Update data baru ke database
        $umkm->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'UMKM updated successfully',
            'data' => $umkm
        ], 200);
    }


    public function show()
    {
        // Check admin role
        $roleCheck = $this->checkAdminRole();
        if ($roleCheck) return $roleCheck;

        // Check if admin has UMKM
        if (!Auth::user()->umkm_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have a UMKM registered'
            ], 400);
        }

        try {
            $umkm = Umkm::findOrFail(Auth::user()->umkm_id);

            return response()->json([
                'status' => 'success',
                'data' => $umkm
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'UMKM not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}