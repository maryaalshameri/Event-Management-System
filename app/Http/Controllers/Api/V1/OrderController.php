<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCollection;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    /**
     * عرض جميع الطلبات
     */
    public function index(Request $request)
    {
        // التحقق من الصلاحية
        if (!Gate::allows('viewAny', Order::class)) {
            abort(403, 'غير مصرح لك بعرض الطلبات');
        }

        $query = Order::with(['user', 'event', 'orderItems.ticketType']);

        // إذا كان المستخدم منظم، عرض طلبات فعالياته فقط
        if ($request->user()->isOrganizer()) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->where('organizer_id', $request->user()->id);
            });
        }

        // إذا كان المستخدم حضور، عرض طلباته فقط
        if ($request->user()->isAttendee()) {
            $query->where('user_id', $request->user()->id);
        }

        $orders = $query->latest()->paginate(10);

        return new OrderCollection($orders);
    }

    /**
     * عرض طلب محدد
     */
    public function show(Order $order)
    {
        // التحقق من الصلاحية
        if (!Gate::allows('view', $order)) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        return new OrderResource($order->load(['user', 'event', 'orderItems.ticketType', 'orderItems.tickets']));
    }

    /**
     * تحديث حالة الطلب
     */
    public function updateStatus(Request $request, Order $order)
    {
        // التحقق من الصلاحية
        if (!Gate::allows('update', $order)) {
            abort(403, 'غير مصرح لك بتحديث هذا الطلب');
        }

        $validated = $request->validate([
            'status' => 'required|in:reserved,confirmed,cancelled',
            'payment_status' => 'sometimes|in:pending,completed,failed,refunded'
        ]);

        $order->update($validated);

        return response()->json([
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'order' => new OrderResource($order)
        ]);
    }
}