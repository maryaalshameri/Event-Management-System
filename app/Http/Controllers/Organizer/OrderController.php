<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
// use App\Models\Order;
// use App\Models\Event;
// use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketGenerated;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;


class OrderController extends Controller
{
  public function index(Request $request)
{
    $user = $request->user();
    
    $query = Order::whereHas('event', function($query) use ($user) {
            $query->where('organizer_id', $user->id);
        })
        ->with(['user', 'event', 'orderItems.ticketType']);
    
    // التصفية حسب حالة الدفع
    if ($request->has('payment_status') && $request->payment_status !== '' && in_array($request->payment_status, ['pending', 'completed', 'failed', 'refunded'])) {
        $query->where('payment_status', $request->payment_status);
    }
    
    // التصفية حسب حالة الطلب
    if ($request->has('status') && $request->status !== '' && in_array($request->status, ['reserved', 'confirmed', 'cancelled'])) {
        $query->where('status', $request->status);
    }
    
    // التصفية حسب الفعالية
    if ($request->has('event_id') && $request->event_id !== '' && is_numeric($request->event_id)) {
        $query->where('event_id', $request->event_id);
    }
    
    // البحث
    if ($request->has('search') && $request->search !== '') {
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

    $orders = $query->latest()->paginate($request->get('per_page', 15));

    // جلب الفعاليات الخاصة بالمنظم للفلتر
    $events = Event::where('organizer_id', $user->id)
        ->select('id', 'title')
        ->orderBy('title') // إضافة ترتيب
        ->get();

    return response()->json([
        'data' => $orders->items(),
        'meta' => [
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
            'from' => $orders->firstItem(),
            'to' => $orders->lastItem(),
        ],
        'events' => $events // إضافة الفعاليات للاستخدام في الفلتر
    ]);
}
    public function show(Order $order)
    {
        if ($order->event->organizer_id !== request()->user()->id) {
            return response()->json(['message' => 'غير مصرح بالوصول لهذا الطلب'], 403);
        }

        $order->load([
            'user', 
            'event', 
            'orderItems.ticketType', 
            'orderItems.tickets'
        ]);

        return response()->json($order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        if ($order->event->organizer_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح بتعديل هذا الطلب'], 403);
        }

        $request->validate([
            'status' => 'sometimes|required|in:reserved,confirmed,cancelled',
            'payment_status' => 'sometimes|required|in:pending,completed,failed,refunded'
        ]);

        $updates = [];
        
        if ($request->has('status')) {
            $updates['status'] = $request->status;
        }
        
        if ($request->has('payment_status')) {
            $updates['payment_status'] = $request->payment_status;
        }

        $order->update($updates);

        return response()->json([
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'order' => $order->fresh()
        ]);
    }

        public function generateTickets(Request $request, Order $order)
    {
        if ($order->event->organizer_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح بتوليد تذاكر لهذا الطلب'], 403);
        }

        // التحقق من شروط توليد التذاكر
        if ($order->status !== 'confirmed') {
            return response()->json(['message' => 'لا يمكن توليد التذاكر إلا للطلبات المؤكدة'], 400);
        }

        if ($order->payment_status !== 'completed') {
            return response()->json(['message' => 'لا يمكن توليد التذاكر إلا للطلبات المكتملة الدفع'], 400);
        }

        // التحقق من عدم وجود تذاكر مسبقة
        $existingTickets = Ticket::whereHas('orderItem', function($query) use ($order) {
            $query->where('order_id', $order->id);
        })->count();

        if ($existingTickets > 0) {
            return response()->json(['message' => 'تم توليد التذاكر مسبقاً لهذا الطلب'], 400);
        }

        try {
            \DB::beginTransaction();

            // إنشاء تذكرة لكل order item
            foreach ($order->orderItems as $orderItem) {
                for ($i = 0; $i < $orderItem->quantity; $i++) {
                    $this->createTicket($orderItem);
                }
            }

            // إرسال البريد الإلكتروني
            $this->sendTicketEmail($order);

            \DB::commit();

            return response()->json([
                'message' => 'تم توليد التذاكر وإرسالها بنجاح',
                'order' => $order->fresh(['orderItems.tickets', 'user', 'event'])
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'message' => 'حدث خطأ أثناء توليد التذاكر',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function createTicket(OrderItem $orderItem)
    {
        // إنشاء كود فريد للتذكرة
        $ticketCode = 'TICKET-' . strtoupper(uniqid());

        // إنشاء QR Code باستخدام endroid/qr-code
        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($ticketCode)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(200)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        $qrPath = 'tickets/qr/' . $ticketCode . '.png';
        Storage::disk('public')->put($qrPath, $qrCode->getString());

        // إنشاء PDF للتذكرة
        $pdf = Pdf::loadView('emails.ticket-pdf', [
            'ticketCode' => $ticketCode,
            'orderItem' => $orderItem,
            'event' => $orderItem->order->event,
            'user' => $orderItem->order->user
        ]);

        $pdfPath = 'tickets/pdf/' . $ticketCode . '.pdf';
        Storage::disk('public')->put($pdfPath, $pdf->output());

        // حفظ التذكرة في قاعدة البيانات
        Ticket::create([
            'order_item_id' => $orderItem->id,
            'code' => $ticketCode,
            'qr_path' => $qrPath,
            'pdf_path' => $pdfPath,
        ]);
    }

    private function sendTicketEmail(Order $order)
    {
        // إرسال البريد الإلكتروني
        Mail::to($order->user->email)->send(new TicketGenerated($order));
    }

    public function downloadTicketPdf(Order $order, Ticket $ticket)
    {
        if ($order->event->organizer_id !== request()->user()->id) {
            abort(403, 'غير مصرح بالوصول لهذه التذكرة');
        }

        if (!Storage::disk('public')->exists($ticket->pdf_path)) {
            abort(404, 'ملف التذكرة غير موجود');
        }

        return response()->download(
            storage_path('app/public/' . $ticket->pdf_path),
            'ticket-' . $ticket->code . '.pdf'
        );
    }
}

  