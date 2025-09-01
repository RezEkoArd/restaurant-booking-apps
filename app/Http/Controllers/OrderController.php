<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Lihat semua order (untuk kasir & pelayan)
     * GET /api/orders
     */
    public function index(Request $request)
    {
        try {
            $query = Order::with(['table', 'user', 'orderItems']);
            //Filter
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $orders = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully',
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to retrieve orders",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buka Order Baru (Pelayan only)
     * POSTS /api/orders
     */

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'table_id' => 'required|exists:tables,id|integer|min:1',
            ]);

            $table = Table::findOrFail($validatedData['table_id']);

            if ($table->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'Table is not available'
                ], 400);
            }

            //Cek apakah ada order yang masih open di meja
            $existingOrder = Order::where('table_id', $validatedData['table_id'])
                ->where('status', 'open')
                ->exists();

            if ($existingOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'There is already an open order for this table',
                ], 400);
            }

            // Buat Order baru
            $order = Order::create([
                'table_id' => $validatedData['table_id'],
                'user_id' => $request->user()->id,
                'status' => 'open',
                'total_price' => 0.00,
                'opened_at' => Carbon::now(),
            ]);

            // Update status meja menjadi occuupied
            $table->update(['status' => 'occupied']);

            return response()->json([
                'success' => false,
                'message' => 'Order Opened successfully',
                'data' => $order->load(['table', 'user'])
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to Open order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lihat detail order
     * GET /api/orders/{id}
     */
    public function show(string $id)
    {
        try {
            $order = Order::with(['table', 'user', 'orderItems.menu'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Order Detail retrieved successfully',
                'data' => $order
            ]);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tambah Item ke Order 
     * POST /api/orders/{id}/items
     */

    public function addItem(Request $request, string $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);

            // Cek order masih Open
            if ($order->status !== 'open') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add items to Close order'
                ], 402);
            }

            // $validatedData = $request->validate([
            //     'menu_id' => 'required|exists:menus,id',
            //     'quantity' => 'required|integer|min:1',
            // ]);

            $validatedData = $request->validate([
                'menu_id' => 'required|exists:menus,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $menu = Menu::findOrFail($validatedData['menu_id']);
            $quantity = $validatedData['quantity'];

            //Convert Price string to decimal
            $price = (float) str_replace(',', '', $menu->price);
            $subtotal = $price * $quantity;

            // Cek items sudah ada di order
            $existingItem = OrderItems::where('order_id', $order->id)
                ->where('menu_id', $validatedData['menu_id'])
                ->first();

            if ($existingItem) {
                //update qty jika udh ada item
                $existingItem->quantity += $quantity;
                $existingItem->subtotal = $price * $existingItem->quantity;
                $existingItem->save();
                $orderItem = $existingItem;
            } else {
                // buat order item baru
                $orderItem = OrderItems::create([
                    'order_id' => $order->id,
                    'menu_id' => $validatedData['menu_id'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            // update total price order
            $this->updatedOrderTotal($order);

            return response()->json([
                'success' => true,
                'message' => 'Item added to order successfully',
                'data' => $orderItem->load('menu')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to order',
                'errors' => $e->errors()
            ], 402);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tutup Order (Pelayan & Kasir)
     * PATCH /api/orders/{id}/close
     */

    public function closeOrder(string $id): JsonResponse
    {
        try {
            $order = Order::with(['table', 'orderItems.menu'])->findOrFail($id);

            if ($order->status !== 'open') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already closed'
                ], 400);
            }

            // update order status dan closed_at
            $order->update([
                'status' => 'close',
                'closed_at' => Carbon::now(),
            ]);

            //update status meja jadi available
            $order->table()->update(['status' => 'available']);

            // itung total pembayaran
            $totalAmount = $this->updatedOrderTotal($order);
            return response()->json([
                'success' => true,
                'message' => 'Order Closed successfully',
                'data' => [
                    'order' => $order->fresh(['table', 'orderItems.menu']),
                    'total_amount' => $totalAmount,
                    'payment_summary' => $this->getPaymentSummary($order)
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not Found'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hitung Pembayaran (Kasir only)
     * GET /api/orders/{id}/payment
     */

    public function calculatedPayment(string $id): JsonResponse
    {
        try {
            $order = Order::with(['orderItems.menu', 'table'])->findOrFail($id);
            $paymentSummary = $this->getPaymentSummary($order);

            return response()->json([
                'success' => true,
                'message' => 'Payment calculated successfully',
                'data' => $paymentSummary,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order Not Found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function untuk update total order
     */

    public function updatedOrderTotal(Order $order): float
    {
        $total = $order->orderItems->sum('subtotal');
        $order->update(['total_price' => $total]);
        return $total;
    }

    private function getPaymentSummary(Order $order): array
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
                    'price' => number_format($item->price, 0, ',', '.'),
                    'subtotal' => number_format($item->subtotal, 0, ',', '.')
                ];
            }),
            'subtotal' => number_format($subtotal, 0, ',', '.'),
            'tax' => number_format($tax, 0, ',', '.'),
            'service_charge' => number_format($serviceCharge, 0, ',', '.'),
            'total_amount' => number_format($total, 0, ',', '.'),
            'opened_at' => $order->opened_at,
            'closed_at' => $order->closed_at,
        ];
    }
}
