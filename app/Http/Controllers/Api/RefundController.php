<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Services\RefundEvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    protected RefundEvaluationService $evaluationService;

    public function __construct(RefundEvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }

    /**
     * List all refund requests with aggregated KPI metrics and optional search/filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RefundRequest::with(['customer', 'order'])->latest();

        // Optional search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('request_id', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('customer_message', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Optional decision filter
        if ($request->filled('decision') && strtoupper($request->input('decision')) !== 'ALL') {
            $query->where('decision', strtoupper($request->input('decision')));
        }

        $allRequests = $query->get();

        // Calculate KPI metrics across all refund records (or current dataset)
        $totalCount = RefundRequest::count();
        $approvedCount = RefundRequest::where('decision', 'APPROVED')->count();
        $deniedCount = RefundRequest::where('decision', 'DENIED')->count();
        $escalatedCount = RefundRequest::where('decision', 'ESCALATED')->count();

        $metrics = [
            'total' => $totalCount,
            'total_requests' => $totalCount,
            'approved' => $approvedCount,
            'denied' => $deniedCount,
            'escalated' => $escalatedCount,
        ];

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
            'data' => $allRequests,
        ]);
    }

    /**
     * Submit a customer refund request (Customer flow).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|string',
            'customer_message' => 'required|string|min:3|max:2000',
            'customer_id' => 'nullable|string',
        ]);

        $refundRequest = $this->evaluationService->evaluate(
            orderId: $validated['order_id'],
            customerMessage: $validated['customer_message'],
            customerId: $validated['customer_id'] ?? null
        );

        return response()->json(array_merge(
            ['success' => true, 'data' => $refundRequest],
            $refundRequest->toArray()
        ), 200);
    }

    /**
     * Authoritative evaluate endpoint (Feature F15).
     */
    public function evaluate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|string',
            'order_id' => 'required|string',
            'customer_message' => 'required|string|min:3|max:2000',
        ]);

        $refundRequest = $this->evaluationService->evaluate(
            orderId: $validated['order_id'],
            customerMessage: $validated['customer_message'],
            customerId: $validated['customer_id']
        );

        return response()->json(array_merge(
            ['success' => true, 'data' => $refundRequest],
            $refundRequest->toArray()
        ), 200);
    }

    /**
     * View detailed audit log for a specific refund request.
     */
    public function show(string $id): JsonResponse
    {
        $record = RefundRequest::with(['customer', 'order'])
            ->where('request_id', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $record,
        ]);
    }

    /**
     * Supervisor manual override of a refund decision (APPROVED or DENIED).
     */
    public function override(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'manual_decision' => 'required|in:APPROVED,DENIED',
            'admin_notes' => 'required|string|min:3',
            'reviewed_by' => 'nullable|string',
        ]);

        $record = RefundRequest::where('request_id', $id)
            ->orWhere('id', $id)
            ->firstOrFail();

        $record->update([
            'manual_decision' => $validated['manual_decision'],
            'admin_notes' => $validated['admin_notes'],
            'reviewed_by' => $validated['reviewed_by'] ?? 'supervisor@store.com',
            'reviewed_at' => now(),
            'status' => 'MANUALLY_OVERRIDDEN',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Manual override applied successfully.',
            'data' => $record->fresh(['customer', 'order']),
        ]);
    }
}
