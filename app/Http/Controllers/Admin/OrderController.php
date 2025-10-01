<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'event', 'orderItems.ticketType']);
        
        // التصفية حسب حالة الدفع
        if ($request->has('payment_status') && in_array($request->payment_status, ['pending', 'completed', 'failed', 'refunded'])) {
            $query->where('payment_status', $request->payment_status);
        }
        
        // التصفية حسب حالة الطلب
        if ($request->has('status') && in_array($request->status, ['reserved', 'confirmed', 'cancelled'])) {
            $query->where('status', $request->status);
        }
        
        // البحث
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('event', function ($q) use ($search) {
                      $q->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // تحديد عدد النتائج
        $limit = $request->get('limit');
        if ($limit) {
            $orders = $query->take($limit)->get();
        } else {
            $orders = $query->paginate($request->get('per_page', 15));
        }

        return response()->json([
            'data' => $orders->items() ?? $orders,
            'meta' => $limit ? null : [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    public function show(Order $order)
    {
        $order->load([
            'user', 
            'event.organizer', 
            'orderItems.ticketType', 
            'orderItems.tickets'
        ]);

        return response()->json($order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'sometimes|required|in:reserved,confirmed,cancelled',
            'payment_status' => 'sometimes|required|in:pending,completed,failed,refunded'
        ]);

        DB::transaction(function () use ($request, $order) {
            $updates = [];
            
            if ($request->has('status')) {
                $updates['status'] = $request->status;
            }
            
            if ($request->has('payment_status')) {
                $updates['payment_status'] = $request->payment_status;
                
                // إذا تم تأكيد الدفع، تأكيد الطلب
                if ($request->payment_status === 'completed') {
                    $updates['status'] = 'confirmed';
                }
            }

            $order->update($updates);
        });

        return response()->json([
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'order' => $order->fresh()
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:csv,json'
        ]);

        $query = Order::with(['user', 'event'])
                    ->where('payment_status', 'completed');

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $orders = $query->get();

        if ($request->get('format') === 'csv') {
            // سيتم تنفيذ تصدير CSV هنا
            return response()->json([
                'message' => 'ميزة التصدير CSV قيد التطوير',
                'orders' => $orders
            ]);
        }

        return response()->json([
            'orders' => $orders,
            'total_revenue' => $orders->sum('total_amount'),
            'total_orders' => $orders->count(),
            'period' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date
            ]
        ]);
    }
}