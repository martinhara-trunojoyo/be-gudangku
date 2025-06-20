<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PetugasController extends Controller
{
    /**
     * Check if user is admin and has UMKM
     */
    private function checkAdminWithUmkm()
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Only admin can perform this action.'
            ], 403);
        }

        if (!Auth::user()->umkm_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You need to have a UMKM before adding staff.'
            ], 400);
        }

        return null;
    }

    /**
     * Display a listing of petugas.
     */
    public function index()
    {
        $check = $this->checkAdminWithUmkm();
        if ($check) return $check;

        try {
            $petugas = User::where('role', 'petugas')
                          ->where('umkm_id', Auth::user()->umkm_id)
                          ->select('id', 'name', 'username', 'email', 'role', 'umkm_id', 'created_at', 'updated_at')
                          ->get();
            if ($petugas->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No petugas found for this UMKM',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Petugas retrieved successfully',
                'data' => $petugas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve petugas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created petugas.
     */
    public function store(Request $request)
    {
        $check = $this->checkAdminWithUmkm();
        if ($check) return $check;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $petugas = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'petugas',
                'umkm_id' => Auth::user()->umkm_id // Inherit admin's UMKM ID
            ]);

            // Remove password from response
            $petugas = $petugas->makeHidden(['password']);

            return response()->json([
                'status' => 'success',
                'message' => 'Petugas created successfully',
                'data' => $petugas
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create petugas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified petugas.
     */
    public function show($id)
    {
        $check = $this->checkAdminWithUmkm();
        if ($check) return $check;

        try {
            $petugas = User::where('role', 'petugas')
                          ->where('umkm_id', Auth::user()->umkm_id)
                          ->where('id', $id)
                          ->select('id', 'name', 'username', 'email', 'role', 'umkm_id', 'created_at', 'updated_at')
                          ->first();

            if (!$petugas) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Petugas not found or not authorized to view'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Petugas retrieved successfully',
                'data' => $petugas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve petugas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified petugas.
     */
    public function update(Request $request, $id)
    {
        $check = $this->checkAdminWithUmkm();
        if ($check) return $check;

        // 1. Mencari data
        $petugas = User::where('role', 'petugas')
                      ->where('umkm_id', Auth::user()->umkm_id)
                      ->where('id', $id)
                      ->first();

        if (!$petugas) {
            return response()->json([
                'status' => 'error',
                'message' => 'Petugas not found or not authorized to update'
            ], 404);
        }

        // 2. Validator
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'required|string|min:6'
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
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ];

        // 4. Update data baru ke database
        $petugas->update($data);

        // Remove password from response
        $petugas = $petugas->makeHidden(['password']);

        return response()->json([
            'status' => 'success',
            'message' => 'Petugas updated successfully',
            'data' => $petugas
        ], 200);
    }

    /**
     * Remove the specified petugas.
     */
    public function destroy($id)
    {
        $check = $this->checkAdminWithUmkm();
        if ($check) return $check;

        try {
            $petugas = User::where('role', 'petugas')
                          ->where('umkm_id', Auth::user()->umkm_id)
                          ->where('id', $id)
                          ->first();

            if (!$petugas) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Petugas not found or not authorized to delete'
                ], 404);
            }

            $petugas->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Petugas deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete petugas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
