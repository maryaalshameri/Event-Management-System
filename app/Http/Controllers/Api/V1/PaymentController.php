<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\OrderItem;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * الحصول على تفاصيل الطلب للدفع
     */
    public function getOrderDetails($orderId)
    {
        try {
            $order = Order::where('user_id', Auth::id())
                ->with(['event', 'orderItems.ticketType', 'tickets'])
                ->findOrFail($orderId);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الطلب',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * معالجة الدفع
     */
    public function processPayment($orderId)
    {
        try {
            $order = Order::where('user_id', Auth::id())
                ->with(['event', 'orderItems.ticketType'])
                ->findOrFail($orderId);

            // إذا كان الطلب مجاني، تأكيد مباشر
            if ($order->total_amount == 0) {
                $order->update([
                    'payment_status' => 'completed',
                    'status' => 'confirmed',
                    'payment_method' => 'free'
                ]);

                // إنشاء ملفات التذاكر
                $this->generateTicketFiles($order);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تأكيد الحجز المجاني بنجاح',
                    'data' => $order
                ]);
            }

            // محاكاة عملية الدفع للطلبات المدفوعة
            $paymentResponse = [
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'currency' => 'SAR',
                'payment_intent' => 'pi_' . uniqid(),
                'status' => 'requires_payment_method',
                'client_secret' => 'cs_test_' . uniqid()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'payment' => $paymentResponse,
                    'next_step' => 'redirect_to_payment_gateway'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في معالجة الدفع',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * نجاح الدفع
     */
  public function paymentSuccess($orderId)
{
    DB::beginTransaction();
    
    try {
        $order = Order::where('user_id', Auth::id())
            ->with(['event', 'orderItems.ticketType', 'tickets'])
            ->findOrFail($orderId);

        // ✅ تحديث حالة الدفع
        $order->update([
            'payment_status' => 'completed',
            'status' => 'confirmed',
            'payment_method' => 'credit_card'
        ]);

        // ✅ إنشاء ملفات التذاكر
        $this->generateTicketFiles($order);

        // ✅ إعادة تحميل البيانات بعد التحديث
        $order->refresh()->load(['event', 'orderItems.ticketType', 'tickets']);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'تم الدفع وإنشاء التذاكر بنجاح',
            'data' => [
                'order' => $order,
                'tickets_count' => $order->tickets->count(),
                'tickets_created' => true
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'خطأ في تأكيد الدفع',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * إلغاء الدفع - تم التصحيح
     */
  /**
 * إلغاء الدفع - محسّن
 */
public function paymentCancel($orderId)
{
    DB::beginTransaction();
    
    try {
        Log::info('=== START PAYMENT CANCELLATION ===');
        Log::info('Order ID: ' . $orderId);
        Log::info('User ID: ' . Auth::id());

        $order = Order::where('user_id', Auth::id())
            ->with(['orderItems.ticketType'])
            ->find($orderId);

        // التحقق من وجود الطلب
        if (!$order) {
            Log::warning('Order not found: ' . $orderId);
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود'
            ], 404);
        }

        Log::info('Order Details:');
        Log::info('- ID: ' . $order->id);
        Log::info('- Status: ' . $order->status);
        Log::info('- Payment Status: ' . $order->payment_status);

        // التحقق من أن الطلب يمكن إلغاؤه
        if ($order->status === 'cancelled') {
            Log::warning('Order already cancelled: ' . $orderId);
            return response()->json([
                'success' => false,
                'message' => 'تم إلغاء هذا الطلب مسبقاً'
            ], 400);
        }

        if ($order->payment_status === 'completed') {
            Log::warning('Order already paid: ' . $orderId);
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء طلب تم دفعه مسبقاً'
            ], 400);
        }

        // إلغاء الطلب
        $order->update([
            'payment_status' => 'failed',
            'status' => 'cancelled'
        ]);

        Log::info('Order status updated to cancelled');

        // إعادة التذاكر إلى المخزون
        $inventoryRestored = false;
        foreach ($order->orderItems as $orderItem) {
            Log::info('Processing order item: ' . $orderItem->id . ', Quantity: ' . $orderItem->quantity);
            
            if ($orderItem->ticketType) {
                $ticketType = $orderItem->ticketType;
                Log::info('Before update - TicketType: ' . $ticketType->name . ', Available: ' . $ticketType->available . ', Sold: ' . $ticketType->sold);
                
                // التأكد من أن القيم صحيحة
                $quantityToRestore = $orderItem->quantity;
                if ($quantityToRestore > 0) {
                    $ticketType->available += $quantityToRestore;
                    $ticketType->sold = max(0, $ticketType->sold - $quantityToRestore);
                    $ticketType->save();
                    
                    $inventoryRestored = true;
                    Log::info('After update - Available: ' . $ticketType->available . ', Sold: ' . $ticketType->sold);
                }
            } else {
                Log::warning('TicketType not found for order item: ' . $orderItem->id);
            }
        }

        DB::commit();
        
        Log::info('=== PAYMENT CANCELLATION COMPLETED SUCCESSFULLY ===');
        Log::info('Inventory restored: ' . ($inventoryRestored ? 'YES' : 'NO'));

        $message = 'تم إلغاء عملية الدفع بنجاح';
        if ($inventoryRestored) {
            $message .= ' وتم إعادة التذاكر إلى المخزون';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'order' => $order,
                'inventory_restored' => $inventoryRestored
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('=== PAYMENT CANCELLATION FAILED ===');
        Log::error('Error: ' . $e->getMessage());
        Log::error('Trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'فشل في إلغاء الطلب: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * إنشاء ملفات التذاكر (PDF و QR)
     */
    private function generateTicketFiles(Order $order)
    {
        foreach ($order->tickets as $ticket) {
            if (!$ticket->pdf_path) {
                // محاكاة إنشاء PDF
                $pdfContent = "تذكرة حضور الفعالية\n";
                $pdfContent .= "رقم التذكرة: {$ticket->code}\n";
                $pdfContent .= "الفعالية: {$order->event->title}\n";
                $pdfContent .= "المكان: {$order->event->location}\n";
                $pdfContent .= "التاريخ: {$order->event->start_date}\n";
                $pdfContent .= "الحضور: {$order->user->name}\n";
                
                $pdfPath = "tickets/{$ticket->code}.pdf";
                Storage::put($pdfPath, $pdfContent);

                // محاكاة إنشاء QR Code
                $qrPath = "qrcodes/{$ticket->code}.png";
                $qrContent = "TICKET:{$ticket->code}|EVENT:{$order->event->id}|USER:{$order->user->id}";
                Storage::put($qrPath, $qrContent);

                $ticket->update([
                    'pdf_path' => $pdfPath,
                    'qr_path' => $qrPath
                ]);
            }
        }
    }
}