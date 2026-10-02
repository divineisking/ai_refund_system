<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CustomerApiController extends Controller
{
    /**
     * Get all customers.
     */
    public function index(): JsonResponse
    {
        $customers = Customer::orderBy('customer_id', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * Get orders for a specific customer.
     */
    public function orders(string $id): JsonResponse
    {
        $customer = Customer::where('customer_id', $id)
            ->when(is_numeric($id), fn ($query) => $query->orWhere('id', (int) $id))
            ->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => "Customer '{$id}' not found.",
            ], 404);
        }

        $orders = Order::where('customer_id', $customer->customer_id)
            ->orderBy('order_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Throwable $e) {
            $dbConnected = false;
        }

        return response()->json([
            'status' => $dbConnected ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'database' => $dbConnected ? 'connected' : 'disconnected',
            'version' => '1.0.0',
        ], $dbConnected ? 200 : 503);
    }
}
