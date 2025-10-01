<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Event::where('organizer_id', $user->id)
            ->with(['ticketTypes', 'orders'])
            ->withCount(['orders', 'reviews']);
        
        // التصفية حسب الحالة
        if ($request->has('status') && in_array($request->status, ['draft', 'published', 'cancelled', 'completed'])) {
            $query->where('status', $request->status);
        }

         // التصفية حسب الفئة
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        // البحث
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'start_date');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $events = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ]
        ]);
    }

  

    public function show(Event $event)
    {
        // التحقق من أن الفعالية تابعة للمنظم
        if ($event->organizer_id !== request()->user()->id) {
            return response()->json(['message' => 'غير مصرح بالوصول لهذه الفعالية'], 403);
        }

        $event->load([
            'ticketTypes', 
            'orders.user', 
            'reviews.user',
            'organizer'
        ]);

        return response()->json($event);
    }

 public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'location' => 'required|string|max:255',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'capacity' => 'required|integer|min:1', // هذا الحقل مطلوب
        'category' => 'required|string|max:100',
        'price' => 'required|numeric|min:0',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'ticket_types' => 'required|array|min:1',
        'ticket_types.*.name' => 'required|string|max:100',
        'ticket_types.*.price' => 'required|numeric|min:0',
        'ticket_types.*.quantity' => 'required|integer|min:1',
        'ticket_types.*.description' => 'nullable|string'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    DB::beginTransaction();
    try {
        $eventData = $request->only([
            'title', 'description', 'start_date', 'end_date', 
            'location', 'latitude', 'longitude', 'capacity', // capacity موجود هنا
            'category', 'price'
        ]);
        
        $eventData['organizer_id'] = $request->user()->id;
        $eventData['status'] = $request->get('status', 'draft');

        // رفع الصورة إذا وجدت
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('events', 'public');
            $eventData['image'] = $imagePath;
        }

        $event = Event::create($eventData);

        // إنشاء أنواع التذاكر
        foreach ($request->ticket_types as $ticketTypeData) {
            TicketType::create([
                'event_id' => $event->id,
                'name' => $ticketTypeData['name'],
                'price' => $ticketTypeData['price'],
                'quantity' => $ticketTypeData['quantity'],
                'available' => $ticketTypeData['quantity'],
                'description' => $ticketTypeData['description'] ?? null,
            ]);
        }

        // إزالة حساب السعة التلقائية لأنها تأتي من الفرونت
        // $totalCapacity = array_sum(array_column($request->ticket_types, 'quantity'));
        // $event->update(['capacity' => $totalCapacity]);

        DB::commit();

        return response()->json([
            'message' => 'تم إنشاء الفعالية بنجاح',
            'event' => $event->load('ticketTypes')
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'حدث خطأ أثناء إنشاء الفعالية',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function update(Request $request, Event $event)
{
    // التحقق من أن الفعالية تابعة للمنظم
    if ($event->organizer_id !== $request->user()->id) {
        return response()->json(['message' => 'غير مصرح بتعديل هذه الفعالية'], 403);
    }

    $validator = Validator::make($request->all(), [
        'title' => 'sometimes|required|string|max:255',
        'description' => 'sometimes|required|string',
        'start_date' => 'sometimes|required|date',
        'end_date' => 'sometimes|required|date|after:start_date',
        'location' => 'sometimes|required|string|max:255',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'capacity' => 'sometimes|required|integer|min:1',
        'category' => 'sometimes|required|string|max:100',
        'price' => 'sometimes|required|numeric|min:0',
        'status' => 'sometimes|required|in:draft,published,cancelled,completed',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'ticket_types' => 'sometimes|required|array|min:1',
        'ticket_types.*.name' => 'sometimes|required|string|max:100',
        'ticket_types.*.price' => 'sometimes|required|numeric|min:0',
        'ticket_types.*.quantity' => 'sometimes|required|integer|min:1',
        'ticket_types.*.description' => 'nullable|string'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    DB::beginTransaction();
    try {
        // ✅ إصلاح: استخدام all() مباشرة للتعامل مع JSON
        $eventData = $request->only([
            'title', 'description', 'start_date', 'end_date', 
            'location', 'latitude', 'longitude', 'capacity', 
            'category', 'price', 'status'
        ]);

        // ✅ إضافة logging للتحقق من البيانات المستلمة
        \Log::info('=== EVENT UPDATE REQUEST ===');
        \Log::info('Request method: ' . $request->method());
        \Log::info('Content-Type: ' . $request->header('Content-Type'));
        \Log::info('All request data:', $request->all());
        \Log::info('Event data to update:', $eventData);
        \Log::info('Event ID: ' . $event->id);
        \Log::info('Current event data:', $event->toArray());

        // ✅ إصلاح: تحقق مما إذا تم إرسال الصورة (لـ FormData فقط)
        if ($request->hasFile('image')) {
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }
            
            $imagePath = $request->file('image')->store('events', 'public');
            $eventData['image'] = $imagePath;
        }

        // ✅ إصلاح: تحديث البيانات مباشرة مع التحقق
        if (!empty($eventData)) {
            \Log::info('Updating event with data:', $eventData);
            
            // تحديث كل حقل على حدة للتحقق
            foreach ($eventData as $field => $value) {
                if ($value !== null) {
                    $event->$field = $value;
                    \Log::info("Updated field: {$field} = {$value}");
                }
            }
            
            $event->save();
            \Log::info('Event saved successfully');
        } else {
            \Log::info('No data to update');
        }

        // ✅ إصلاح: تحديث أنواع التذاكر
        if ($request->has('ticket_types')) {
            \Log::info('Updating ticket types:', $request->ticket_types);
            
            // حذف التذاكر القديمة
            $event->ticketTypes()->delete();
            \Log::info('Old ticket types deleted');
            
            // إنشاء التذاكر الجديدة
            foreach ($request->ticket_types as $ticketTypeData) {
                TicketType::create([
                    'event_id' => $event->id,
                    'name' => $ticketTypeData['name'],
                    'price' => $ticketTypeData['price'],
                    'quantity' => $ticketTypeData['quantity'],
                    'available' => $ticketTypeData['quantity'],
                    'description' => $ticketTypeData['description'] ?? null,
                ]);
                \Log::info("Created ticket type: {$ticketTypeData['name']}");
            }
            
            \Log::info('Ticket types updated successfully');
        }

        DB::commit();

        // ✅ إصلاح: إعادة تحميل البيانات من قاعدة البيانات
        $event->refresh();
        $event->load('ticketTypes');

        \Log::info('=== EVENT UPDATE COMPLETED ===');
        \Log::info('Final event data:', $event->toArray());

        return response()->json([
            'message' => 'تم تحديث الفعالية بنجاح',
            'event' => $event
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('=== EVENT UPDATE ERROR ===');
        \Log::error('Error message: ' . $e->getMessage());
        \Log::error('Error trace: ' . $e->getTraceAsString());
        return response()->json([
            'message' => 'حدث خطأ أثناء تحديث الفعالية',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function destroy(Event $event)
    {
        // التحقق من أن الفعالية تابعة للمنظم
        if ($event->organizer_id !== request()->user()->id) {
            return response()->json(['message' => 'غير مصرح بحذف هذه الفعالية'], 403);
        }

        // حذف الصورة إذا وجدت
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return response()->json([
            'message' => 'تم حذف الفعالية بنجاح'
        ]);
    }

    public function updateStatus(Request $request, Event $event)
    {
        // التحقق من أن الفعالية تابعة للمنظم
        if ($event->organizer_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح بتعديل حالة هذه الفعالية'], 403);
        }

        $request->validate([
            'status' => 'required|in:draft,published,cancelled,completed'
        ]);

        $event->update(['status' => $request->status]);

        return response()->json([
            'message' => 'تم تحديث حالة الفعالية بنجاح',
            'event' => $event
        ]);
    }

    public function attendees(Event $event)
    {
        // التحقق من أن الفعالية تابعة للمنظم
        if ($event->organizer_id !== request()->user()->id) {
            return response()->json(['message' => 'غير مصرح بالوصول لهذه الفعالية'], 403);
        }

        $attendees = $event->orders()
            ->where('payment_status', 'completed')
            ->with(['user', 'orderItems.ticketType'])
            ->get()
            ->map(function($order) {
                return [
                    'order_id' => $order->id,
                    'user_name' => $order->user->name,
                    'user_email' => $order->user->email,
                    'ticket_type' => $order->orderItems->first()->ticketType->name,
                    'quantity' => $order->orderItems->sum('quantity'),
                    'total_amount' => $order->total_amount,
                    'ordered_at' => $order->created_at,
                ];
            });

        return response()->json($attendees);
    }


    public function reviews(Event $event)
{
    // التحقق من أن الفعالية تابعة للمنظم
    if ($event->organizer_id !== request()->user()->id) {
        return response()->json(['message' => 'غير مصرح بالوصول لهذه الفعالية'], 403);
    }

    $reviews = $event->reviews()
        ->with('user')
        ->orderBy('created_at', 'desc')
        ->paginate(request()->get('per_page', 10));

    return response()->json([
        'data' => $reviews->items(),
        'meta' => [
            'current_page' => $reviews->currentPage(),
            'last_page' => $reviews->lastPage(),
            'per_page' => $reviews->perPage(),
            'total' => $reviews->total(),
        ]
    ]);
}
}