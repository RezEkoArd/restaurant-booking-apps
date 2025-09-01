<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class ReceiptController extends Controller
{
    /**
     * Generate dan download receipt PDF
     */
    public function downloadReceipt(string $orderId): BaseResponse
    {
        try {
            $order = Order::with(['orderItems.menu', 'table'])->findOrFail($orderId);

            // Validasi: hanya bisa generate PDF untuk order yang sudah closed
            if (!$order->closed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot generate receipt for open orders',
                ], 400);
            }

            $paymentData = $this->getReceiptData($order);

            $pdf = Pdf::loadView('receipts.order-receipt', compact('order', 'paymentData'))
                ->setPaper('a4', 'portrait')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $filename = $this->generateReceiptFilename($order);

            return $pdf->download($filename);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview receipt PDF di browser
     */
    public function previewReceipt(string $orderId): BaseResponse
    {
        try {
            $order = Order::with(['orderItems.menu', 'table'])->findOrFail($orderId);
            $paymentData = $this->getReceiptData($order);

            $pdf = Pdf::loadView('receipts.order-receipt', compact('order', 'paymentData'))
                ->setPaper('a4', 'portrait')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $filename = 'receipt-preview-' . $order->id . '.pdf';

            return $pdf->stream($filename, ['Attachment' => false]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate receipt untuk multiple orders (batch)
     */
    public function downloadBatchReceipt(array $orderIds): BaseResponse
    {
        try {
            $orders = Order::with(['orderItems.menu', 'table'])
                ->whereIn('id', $orderIds)
                ->whereNotNull('closed_at')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid closed orders found',
                ], 404);
            }

            $allReceiptData = $orders->map(function ($order) {
                return [
                    'order' => $order,
                    'paymentData' => $this->getReceiptData($order)
                ];
            });

            $pdf = Pdf::loadView('receipts.batch-receipt', compact('allReceiptData'))
                ->setPaper('a4', 'portrait')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10);

            $filename = 'batch-receipts-' . now()->format('Y-m-d-H-i-s') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate batch receipts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get summary info untuk receipt (tanpa format currency)
     */
    private function getReceiptData(Order $order): array
    {
        $subtotal = $order->orderItems->sum('subtotal');
        $tax = $subtotal * 0.11; // PPN 11%
        $serviceCharge = $subtotal * 0.05; // Service charge 5%
        $total = $subtotal + $tax + $serviceCharge;

        return [
            'order_id' => $order->id,
            'table_number' => $order->table->table_no,
            'items' => $order->orderItems->map(function ($item) {
                return [
                    'menu_name' => $item->menu->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal
                ];
            }),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'service_charge' => $serviceCharge,
            'total_amount' => $total,
            'opened_at' => $order->opened_at,
            'closed_at' => $order->closed_at,
            'generated_at' => now(),
            'company' => [
                'name' => config('app.name', 'Warung DiSayurin Warga'),
                'address' => config('receipt.company_address', 'Jl. In Aja Dulu No. 123, Jakarta Pusat'),
                'phone' => config('receipt.company_phone', '(021) 1234-5678'),
                'email' => config('receipt.company_email', 'info@wartegsayurin.com'),
            ],
        ];
    }

    /**
     * Generate filename untuk receipt
     */
    private function generateReceiptFilename(Order $order): string
    {
        $date = Carbon::parse($order->closed_at ?? $order->opened_at)->format('Y-m-d');
        return "receipt-{$order->id}-table-{$order->table->table_no}-{$date}.pdf";
    }

    /**
     * Get receipt data dalam format JSON (untuk API)
     */
    public function getReceiptJson(string $orderId): JsonResponse
    {
        try {
            $order = Order::with(['orderItems.menu', 'table'])->findOrFail($orderId);
            $receiptData = $this->getReceiptData($order);

            return response()->json([
                'success' => true,
                'message' => 'Receipt data retrieved successfully',
                'data' => $receiptData,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get receipt data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
