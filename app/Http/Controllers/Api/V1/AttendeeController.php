<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

use Barryvdh\DomPDF\Facade\Pdf; 



class AttendeeController extends Controller
{
    /**
     * عرض الفعاليات المتاحة
     */
    public function events(Request $request)
    {
        try {
            $query = Event::with(['organizer', 'ticketTypes'])
                ->where('status', 'published')
                ->where('start_date', '>', now());

            // فلترة حسب المدينة
            if ($request->has('city') && $request->city) {
                $query->where('location', 'like', '%' . $request->city . '%');
            }

            // فلترة حسب الفئة
            if ($request->has('category') && $request->category) {
                $query->where('category', $request->category);
            }

            // فلترة حسب التاريخ
            if ($request->has('date') && $request->date) {
                $query->whereDate('start_date', $request->date);
            }

            // البحث
            if ($request->has('search') && $request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            // الترتيب
            $sortBy = $request->get('sort_by', 'start_date');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $events = $query->paginate(12);

            // تحويل البيانات لإضافة image_url
            $events->getCollection()->transform(function ($event) {
                if ($event->image) {
                    $event->image_url = Storage::url($event->image);
                } else {
                    $event->image_url = null;
                }
                return $event;
            });

            return response()->json([
                'success' => true,
                'data' => $events,
                'message' => 'تم جلب الفعاليات بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الفعاليات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض تفاصيل فعالية محددة
     */
    public function showEvent($id)
    {
        $event = Event::with(['organizer', 'ticketTypes', 'reviews.user'])
            ->where('status', 'published')
            ->findOrFail($id);

        // إضافة URL كامل للصورة
        if ($event->image) {
            $event->image_url = Storage::url($event->image);
        } else {
            $event->image_url = null;
        }

        // حساب متوسط التقييم
        $averageRating = $event->reviews->avg('rating');
        $totalReviews = $event->reviews->count();

        return response()->json([
            'success' => true,
            'data' => [
                'event' => $event,
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $totalReviews
            ]
        ]);
    }

    /**
     * إنشاء طلب شراء تذاكر
     */
 /**
 * إنشاء طلب شراء تذاكر
 */
/**
 * إنشاء طلب شراء تذاكر
 */
public function createOrder(Request $request)
{
    $request->validate([
        'event_id' => 'required|exists:events,id',
        'tickets' => 'required|array|min:1',
        'tickets.*.ticket_type_id' => 'required|exists:ticket_types,id',
        'tickets.*.quantity' => 'required|integer|min:1'
    ]);

    $event = Event::findOrFail($request->event_id);

    // التحقق من توفر التذاكر
    $totalAmount = 0;
    $orderItems = [];

    foreach ($request->tickets as $ticketRequest) {
        $ticketType = $event->ticketTypes()
            ->where('id', $ticketRequest['ticket_type_id'])
            ->first();

        if (!$ticketType) {
            return response()->json([
                'success' => false,
                'message' => 'نوع التذكرة غير موجود'
            ], 404);
        }

        if ($ticketType->available < $ticketRequest['quantity']) {
            return response()->json([
                'success' => false,
                'message' => "لا توجد تذاكر كافية من نوع {$ticketType->name}"
            ], 400);
        }

        $subtotal = $ticketType->price * $ticketRequest['quantity'];
        $totalAmount += $subtotal;

        $orderItems[] = [
            'ticket_type' => $ticketType,
            'quantity' => $ticketRequest['quantity'],
            'subtotal' => $subtotal
        ];
    }

    // إنشاء الطلب
    $order = Order::create([
        'user_id' => Auth::id(),
        'event_id' => $event->id,
        'status' => 'reserved',
        'total_amount' => $totalAmount,
        'payment_status' => 'pending',
        'notes' => $request->notes
    ]);

    // إنشاء عناصر الطلب
    foreach ($orderItems as $item) {
        $orderItem = $order->orderItems()->create([
            'ticket_type_id' => $item['ticket_type']->id,
            'quantity' => $item['quantity'],
            'price' => $item['ticket_type']->price
        ]);

        // تحديث التذاكر المتاحة
        $item['ticket_type']->decrement('available', $item['quantity']);
        $item['ticket_type']->increment('sold', $item['quantity']);

        // إنشاء التذاكر
        for ($i = 0; $i < $item['quantity']; $i++) {
            $ticketCode = 'TICKET-' . strtoupper(uniqid());
            
            Ticket::create([
                'order_item_id' => $orderItem->id,
                'code' => $ticketCode,
                'pdf_path' => null,
                'qr_path' => null
            ]);
        }
    }

    // ✅ استخدام URL مباشرة بدلاً من route
    $paymentUrl = $totalAmount > 0 ? "/payment/process/{$order->id}" : null;

    return response()->json([
        'success' => true,
        'data' => [
            'order' => $order->load('orderItems.ticketType', 'tickets'),
            'payment_url' => $paymentUrl,
            'order_id' => $order->id // ✅ إضافة order_id للاستخدام في الـ frontend
        ],
        'message' => 'تم إنشاء الطلب بنجاح'
    ]);
}

    /**
     * تأكيد الدفع
     */
    public function confirmPayment($orderId)
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($orderId);

        // محاكاة عملية الدفع (يمكن استبدالها بـ Stripe)
        $order->update([
            'payment_status' => 'completed',
            'status' => 'confirmed',
            'payment_method' => 'credit_card'
        ]);

        // إنشاء PDF و QR Code للتذاكر
        foreach ($order->tickets as $ticket) {
            $this->generateTicketFiles($ticket);
        }

        // إرسال إشعار بالبريد
        // Mail::to(Auth::user()->email)->send(new TicketPurchased($order));

        return response()->json([
            'success' => true,
            'data' => $order->load('event', 'tickets'),
            'message' => 'تم تأكيد الدفع بنجاح'
        ]);
    }

    /**
     * عرض تذاكر المستخدم
     */
  public function myTickets(Request $request)
{
    $query = Order::with([
        'event', 
        'tickets.orderItem.ticketType', // ✅ تحميل العلاقات المتداخلة
        'orderItems.ticketType'
    ])
    ->where('user_id', Auth::id())
    ->where('payment_status', 'completed');

    if ($request->has('status')) {
        $query->where('status', $request->status);
    }

    $orders = $query->orderBy('created_at', 'desc')
        ->paginate(10);

    return response()->json([
        'success' => true,
        'data' => $orders->items(), // ✅ استخدام items() للحصول على المصفوفة
        'meta' => [
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total()
        ]
    ]);
}

    public function createReview(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000'
        ]);

        // التحقق من أن المستخدم حضر الفعالية
        $hasAttended = Order::where('user_id', Auth::id())
            ->where('event_id', $request->event_id)
            ->where('status', 'confirmed')
            ->where('payment_status', 'completed')
            ->exists();

        if (!$hasAttended) {
            return response()->json([
                'success' => false,
                'message' => 'يجب أن تكون قد حضرت الفعالية لتتمكن من تقييمها'
            ], 403);
        }

        // التحقق من عدم التقييم مسبقاً
        $existingReview = Review::where('user_id', Auth::id())
            ->where('event_id', $request->event_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'لقد قمت بتقييم هذه الفعالية مسبقاً'
            ], 400);
        }

        $review = Review::create([
            'user_id' => Auth::id(),
            'event_id' => $request->event_id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json([
            'success' => true,
            'data' => $review->load('user'),
            'message' => 'تم إضافة التقييم بنجاح'
        ]);
    }

    /**
     * إنشاء ملفات التذكرة (PDF و QR)
     */
    private function generateTicketFiles(Ticket $ticket)
    {
        // محاكاة إنشاء PDF (يمكن استخدام dompdf أو مكتبة أخرى)
        $pdfContent = "Ticket: {$ticket->code}\nEvent: {$ticket->orderItem->order->event->title}";
        $pdfPath = "tickets/{$ticket->code}.pdf";
        
        Storage::put($pdfPath, $pdfContent);

        // محاكاة إنشاء QR Code
        $qrPath = "qrcodes/{$ticket->code}.png";
        Storage::put($qrPath, "QR Code for: {$ticket->code}");

        $ticket->update([
            'pdf_path' => $pdfPath,
            'qr_path' => $qrPath
        ]);
    }























    /**
     * إنشاء QR Code (محسّن)
     */
    private function generateQRCode(Ticket $ticket)
    {
        try {
            $order = $ticket->orderItem->order;
            $event = $order->event;

            // بيانات QR Code
            $qrData = [
                'ticket_code' => $ticket->code,
                'event_id' => $event->id,
                'event_title' => $event->title,
                'user_id' => $order->user->id,
                'user_name' => $order->user->name,
                'timestamp' => now()->toISOString()
            ];

            // محاكاة إنشاء QR Code كصورة PNG
            $qrContent = "QR Code Data: " . json_encode($qrData);
            
            // في التطبيق الحقيقي، استخدم مكتبة مثل:
            // use SimpleSoftwareIO\QrCode\Facades\QrCode;
            // $qrImage = QrCode::format('png')->size(300)->generate(json_encode($qrData));
            
            $qrPath = "qrcodes/{$ticket->code}.png";
            Storage::put($qrPath, $qrContent);

            $ticket->update([
                'qr_path' => $qrPath
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error generating QR code: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * إنشاء PDF للتذكرة - محسّن
     */
    private function generateTicketPDF(Ticket $ticket)
    {
        try {
            Log::info('Generating PDF for ticket: ' . $ticket->code);

            $order = $ticket->orderItem->order;
            $event = $order->event;
            $ticketType = $ticket->orderItem->ticketType;

            // محتوى HTML للتذكرة
            $htmlContent = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
                    <title>تذكرة {$event->title}</title>
                    <style>
                        body { 
                            font-family: 'DejaVu Sans', 'Arial', sans-serif; 
                            direction: rtl; 
                            text-align: right; 
                            margin: 0;
                            padding: 20px;
                            background: #f8fafc;
                        }
                        .ticket-container {
                            max-width: 600px;
                            margin: 0 auto;
                            background: white;
                            border: 2px solid #4f46e5;
                            border-radius: 15px;
                            padding: 25px;
                            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 25px;
                            padding-bottom: 15px;
                            border-bottom: 2px dashed #e5e7eb;
                        }
                        .event-title {
                            font-size: 24px;
                            font-weight: bold;
                            color: #4f46e5;
                            margin-bottom: 10px;
                        }
                        .ticket-code {
                            background: #4f46e5;
                            color: white;
                            padding: 8px 15px;
                            border-radius: 8px;
                            font-family: monospace;
                            font-size: 16px;
                            display: inline-block;
                            margin: 10px 0;
                        }
                        .ticket-info {
                            margin: 15px 0;
                            padding: 12px;
                            background: #f8fafc;
                            border-radius: 8px;
                            border-right: 4px solid #4f46e5;
                        }
                        .info-label {
                            font-weight: bold;
                            color: #374151;
                            margin-bottom: 5px;
                        }
                        .info-value {
                            color: #6b7280;
                        }
                        .qr-section {
                            text-align: center;
                            margin: 25px 0;
                            padding: 20px;
                            background: #f0f9ff;
                            border-radius: 10px;
                        }
                        .qr-placeholder {
                            width: 150px;
                            height: 150px;
                            background: white;
                            margin: 0 auto;
                            border: 2px dashed #4f46e5;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 12px;
                            color: #6b7280;
                        }
                        .footer {
                            text-align: center;
                            margin-top: 25px;
                            padding-top: 15px;
                            border-top: 1px dashed #e5e7eb;
                            color: #6b7280;
                            font-size: 12px;
                        }
                    </style>
                </head>
                <body>
                    <div class='ticket-container'>
                        <div class='header'>
                            <div class='event-title'>{$event->title}</div>
                            <div style='color: #6b7280; margin-bottom: 15px;'>تذكرة حضور الفعالية</div>
                            <div class='ticket-code'>{$ticket->code}</div>
                        </div>
                        
                        <div class='ticket-info'>
                            <div class='info-label'>معلومات التذكرة</div>
                            <div class='info-value'>نوع التذكرة: {$ticketType->name}</div>
                            <div class='info-value'>الحالة: " . ($ticket->used_at ? 'مستخدمة' : 'نشطة') . "</div>
                        </div>
                        
                        <div class='ticket-info'>
                            <div class='info-label'>معلومات الفعالية</div>
                            <div class='info-value'>الفعالية: {$event->title}</div>
                            <div class='info-value'>المكان: {$event->location}</div>
                            <div class='info-value'>التاريخ: " . date('Y-m-d H:i', strtotime($event->start_date)) . "</div>
                        </div>
                        
                        <div class='ticket-info'>
                            <div class='info-label'>معلومات الحضور</div>
                            <div class='info-value'>الاسم: {$order->user->name}</div>
                            <div class='info-value'>البريد: {$order->user->email}</div>
                        </div>
                        
                        <div class='qr-section'>
                            <div style='font-weight: bold; margin-bottom: 15px; color: #4f46e5;'>رمز الاستجابة السريعة</div>
                            <div class='qr-placeholder'>
                                QR Code<br>{$ticket->code}
                            </div>
                        </div>
                        
                        <div class='footer'>
                            <div>يرجى عرض هذه التذكرة عند الدخول للفعالية</div>
                            <div>تم الإنشاء في: " . now()->format('Y-m-d H:i') . "</div>
                        </div>
                    </div>
                </body>
                </html>
            ";

            // حفظ المحتوى كملف PDF
            $pdfPath = "tickets/{$ticket->code}.pdf";
            
            // التأكد من وجود المجلد
            if (!Storage::exists('tickets')) {
                Storage::makeDirectory('tickets');
            }

            Storage::put($pdfPath, $htmlContent);

            // تحديث قاعدة البيانات
            $ticket->update([
                'pdf_path' => $pdfPath
            ]);

            Log::info('PDF generated successfully: ' . $pdfPath);
            return true;

        } catch (\Exception $e) {
            Log::error('Error generating ticket PDF: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * تحميل جميع تذاكر الطلب في ملف ZIP - مصححة
     */
    public function downloadAllTickets($orderId)
    {
        try {
            Log::info('=== START ALL TICKETS DOWNLOAD ===');
            Log::info('Order ID: ' . $orderId);

            $order = Order::where('user_id', Auth::id())
                ->with(['tickets.orderItem.ticketType', 'event'])
                ->findOrFail($orderId);

            Log::info('Order found, tickets count: ' . $order->tickets->count());

            if ($order->tickets->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد تذاكر في هذا الطلب'
                ], 404);
            }

            // إنشاء مجلد مؤقت إذا لم يكن موجوداً
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $zipFileName = "tickets-order-{$order->id}.zip";
            $zipPath = "{$tempDir}/{$zipFileName}";

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                $pdfsAdded = 0;
                
                foreach ($order->tickets as $ticket) {
                    Log::info('Processing ticket: ' . $ticket->code);
                    
                    // إنشاء PDF إذا لم يكن موجوداً
                    if (!$ticket->pdf_path || !Storage::exists($ticket->pdf_path)) {
                        Log::info('Generating PDF for ticket: ' . $ticket->code);
                        $this->generateTicketPDF($ticket);
                        $ticket->refresh();
                    }

                    if (Storage::exists($ticket->pdf_path)) {
                        $fileContent = Storage::get($ticket->pdf_path);
                        $zip->addFromString("ticket-{$ticket->code}.pdf", $fileContent);
                        $pdfsAdded++;
                        Log::info('PDF added to ZIP: ' . $ticket->code);
                    } else {
                        Log::warning('PDF not found for ticket: ' . $ticket->code);
                    }
                }
                
                $zip->close();
                Log::info('ZIP created successfully, PDFs added: ' . $pdfsAdded);
            } else {
                throw new \Exception('Failed to create ZIP file');
            }

            if (!file_exists($zipPath)) {
                throw new \Exception('ZIP file was not created');
            }

            // إعداد headers للتحميل
            $headers = [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
                'Content-Length' => filesize($zipPath),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ];

            Log::info('=== ALL TICKETS DOWNLOAD COMPLETED ===');

            return Response::download($zipPath, $zipFileName, $headers)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error downloading all tickets: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في تحميل التذاكر: ' . $e->getMessage()
            ], 500);
        }
    }










public function downloadTicket($ticketId)
{
    try {
        \Log::info('=== START TICKET DOWNLOAD ===');
        \Log::info('Ticket ID: ' . $ticketId);
        \Log::info('User ID: ' . Auth::id());

        // تحميل التذكرة مع العلاقات المطلوبة
        $ticket = Ticket::with([
            'orderItem.order.event',
            'orderItem.order.user', 
            'orderItem.ticketType'
        ])
        ->whereHas('orderItem.order', function ($query) {
            $query->where('user_id', Auth::id());
        })
        ->findOrFail($ticketId);

        \Log::info('Ticket found: ' . $ticket->code);
        \Log::info('Event: ' . $ticket->orderItem->order->event->title);

        // إذا لم يكن ملف PDF موجود، إنشائه
        if (!$ticket->pdf_path || !\Storage::exists($ticket->pdf_path)) {
            \Log::info('PDF not found, generating new PDF...');
            $this->generateRealTicketPDF($ticket);
            $ticket->refresh();
        }

        // التحقق مرة أخرى بعد الإنشاء
        if (!\Storage::exists($ticket->pdf_path)) {
            \Log::error('PDF file still not exists after generation: ' . $ticket->pdf_path);
            return response()->json([
                'success' => false,
                'message' => 'ملف التذكرة غير موجود'
            ], 404);
        }

        \Log::info('PDF file exists, proceeding with download: ' . $ticket->pdf_path);

        return \Storage::download($ticket->pdf_path, "ticket-{$ticket->code}.pdf");

    } catch (\Exception $e) {
        \Log::error('Error downloading ticket PDF: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'خطأ في تحميل التذكرة: ' . $e->getMessage()
        ], 500);
    }
}





private function generateRealTicketPDF(Ticket $ticket)
{
    try {
        \Log::info('Generating REAL PDF for ticket: ' . $ticket->code);

        // تحميل العلاقات المطلوبة
        $ticket->load([
            'orderItem.order.event',
            'orderItem.order.user',
            'orderItem.ticketType'
        ]);

        $order = $ticket->orderItem->order;
        $event = $order->event;
        $ticketType = $ticket->orderItem->ticketType;
        $user = $order->user;

        // بيانات للتذكرة
        $data = [
            'ticket' => $ticket,
            'event' => $event,
            'ticketType' => $ticketType,
            'user' => $user,
            'order' => $order,
            'generated_at' => now()->format('Y-m-d H:i')
        ];

        // التأكد من وجود مجلد التذاكر
        if (!\Storage::exists('tickets')) {
            \Storage::makeDirectory('tickets');
        }

        // إنشاء PDF باستخدام DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('tickets.ticket-pdf', $data);
        
        // إعدادات PDF
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'dpi' => 96,
            'chroot' => [realpath(base_path())]
        ]);

        // حفظ PDF
        $pdfPath = "tickets/{$ticket->code}.pdf";
        \Storage::put($pdfPath, $pdf->output());

        // تحديث قاعدة البيانات
        $ticket->update([
            'pdf_path' => $pdfPath
        ]);

        \Log::info('Real PDF generated successfully: ' . $pdfPath);
        return true;

    } catch (\Exception $e) {
        \Log::error('Error generating real ticket PDF: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        // Fallback: إنشاء PDF بسيط إذا فشل DomPDF
        return $this->generateSimplePDF($ticket);
    }
}



    /**
     * إنشاء PDF بسيط كبديل إذا فشل DomPDF
     */
    private function generateSimplePDF(Ticket $ticket)
    {
        try {
            $order = $ticket->orderItem->order;
            $event = $order->event;
            $ticketType = $ticket->orderItem->ticketType;

            // محتوى نصي بسيط للتذكرة
            $pdfContent = "
                تذكرة حضور الفعالية
                ====================
                
                رقم التذكرة: {$ticket->code}
                الفعالية: {$event->title}
                المكان: {$event->location}
                التاريخ: " . date('Y-m-d H:i', strtotime($event->start_date)) . "
                
                نوع التذكرة: {$ticketType->name}
                الحالة: " . ($ticket->used_at ? 'مستخدمة' : 'نشطة') . "
                
                معلومات الحضور:
                الاسم: {$order->user->name}
                البريد: {$order->user->email}
                
                رمز الاستجابة السريعة: {$ticket->code}
                
                ====================
                يرجى عرض هذه التذكرة عند الدخول للفعالية
                تم الإنشاء في: " . now()->format('Y-m-d H:i') . "
            ";

            $pdfPath = "tickets/{$ticket->code}.pdf";
            Storage::put($pdfPath, $pdfContent);

            $ticket->update([
                'pdf_path' => $pdfPath
            ]);

            Log::info('Simple PDF generated as fallback: ' . $pdfPath);
            return true;

        } catch (\Exception $e) {
            Log::error('Error generating simple PDF: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * تحميل صورة QR Code
     */
    public function downloadQRCode($ticketId)
    {
        try {
            Log::info('=== START QR CODE DOWNLOAD ===');
            Log::info('Ticket ID: ' . $ticketId);

            $ticket = Ticket::with(['orderItem.order.event', 'orderItem.ticketType'])
                ->whereHas('orderItem.order', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->findOrFail($ticketId);

            Log::info('Ticket found: ' . $ticket->code);

            // إذا لم يكن ملف QR موجود، إنشائه
            if (!$ticket->qr_path || !Storage::exists($ticket->qr_path)) {
                Log::info('QR not found, generating new QR...');
                $this->generateQRCode($ticket);
                $ticket->refresh();
            }

            if (!Storage::exists($ticket->qr_path)) {
                Log::error('QR file still not exists after generation');
                return response()->json([
                    'success' => false,
                    'message' => 'ملف QR Code غير موجود'
                ], 404);
            }

            Log::info('QR file exists, proceeding with download: ' . $ticket->qr_path);

            return Storage::download($ticket->qr_path, "qr-{$ticket->code}.png");

        } catch (\Exception $e) {
            Log::error('Error downloading QR code: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في تحميل QR Code'
            ], 500);
        }
    }

    
}