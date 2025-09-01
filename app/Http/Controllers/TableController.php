<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableController extends Controller
{
    /**
     * Lihat semua meja (untuk tamu - tanpa login)
     * GET /api/tables
     */
    public function index(): JsonResponse
    {
        try {
            $tables = Table::orderBy('table_no', 'asc')->get();

            $tablesByStatus = [
                'available' => $tables->where('status', 'available')->values(),
                'occupied' => $tables->where('status', 'occupied')->values(),
                'reserved' => $tables->where('status', 'reserved')->values(),
                'maintenance' => $tables->where('status', 'maintenance')->values(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Table retrieved successfully',
                'data' => [
                    'all_tables' => $tables,
                    'by_status' => $tablesByStatus,
                    'summary' => [
                        'total_table' => $tables->count(),
                        'available' => $tables->where('status', 'available')->count(),
                        'occupied' => $tables->where('status', 'occupied')->count(),
                        'reserved' => $tables->where('status', 'reserved')->count(),
                        'maintenance' => $tables->where('status', 'maintenance')->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieved tables',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lihat meja yang tersedia (untuk buka order)
     * GET /api/tables/available
     */

    public function getAvailable(): JsonResponse
    {
        try {
            $availableTables = Table::where('status', 'available')
                ->orderBy('table_no', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Failed to retrieve available tables',
                'data' => $availableTables
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available tables',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
