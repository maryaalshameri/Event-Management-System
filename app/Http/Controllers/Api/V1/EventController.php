<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Event;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\EventResource;

class EventController extends Controller
{
    // إحصائيات Dashboard
    public function dashboardStats(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'activeEvents' => Event::where('organizer_id', $user->id)
                ->where('status', 'published')
                ->where('start_date', '>', now())
                ->count(),
                
            'ticketsSold' => \App\Models\Order::whereHas('event', function($q) use ($user) {
                    $q->where('organizer_id', $user->id);
                })
                ->where('payment_status', 'completed')
                ->count(),
                
            'revenue' => \App\Models\Order::whereHas('event', function($q) use ($user) {
                    $q->where('organizer_id', $user->id);
                })
                ->where('payment_status', 'completed')
                ->sum('total_amount')
        ];

        return response()->json($stats);
    }

    // عرض فعاليات المنظم
    public function index(Request $request)
    {
        $query = Event::where('organizer_id', $request->user()->id);

        // التصفية
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $perPage = $request->per_page ?? 10;
        $events = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return EventResource::collection($events);
    }

    // إنشاء فعالية جديدة
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'location' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity' => 'required|integer|min:1',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,published'
        ]);

        // معالجة الصورة
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('events', 'public');
        }

        $validated['organizer_id'] = $request->user()->id;
        $validated['available_seats'] = $validated['capacity'];

        $event = Event::create($validated);

        return response()->json([
            'message' => 'تم إنشاء الفعالية بنجاح',
            'data' => new EventResource($event)
        ], 201);
    }

    // عرض فعالية محددة
    public function show(Event $event)
    {
        // التحقق من أن المستخدم هو المنظم
        if ($event->organizer_id !== request()->user()->id) {
            return response()->json(['message' => 'غير مصرح لك بالوصول إلى هذه الفعالية'], 403);
        }

        return new EventResource($event->load(['organizer']));
    }

    // تحديث فعالية
    public function update(Request $request, Event $event)
    {
        if ($event->organizer_id !== $request->user()->id) {
            return response()->json(['message' => 'غير مصرح لك بتحديث هذه الفعالية'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'start_date' => 'sometimes|date|after:now',
            'end_date' => 'sometimes|date|after:start_date',
            'location' => 'sometimes|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity' => 'sometimes|integer|min:1',
            'category' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'status' => 'sometimes|in:draft,published,cancelled'
        ]);

        // معالجة الصورة
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }
            $validated['image'] = $request->file('image')->store('events', 'public');
        }

        $event->update($validated);

        return response()->json([
            'message' => 'تم تحديث الفعالية بنجاح',
            'data' => new EventResource($event)
        ]);
    }

    // حذف فعالية
    public function destroy(Event $event)
    {
        if ($event->organizer_id !== request()->user()->id) {
            return response()->json(['message' => 'غير مصرح لك بحذف هذه الفعالية'], 403);
        }

        // حذف الصورة
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return response()->json([
            'message' => 'تم حذف الفعالية بنجاح'
        ]);
    }

    // للعامة - عرض الفعاليات
    public function indexPublic()
    {
        $events = Event::where('status', 'published')
            ->where('start_date', '>', now())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return EventResource::collection($events);
    }

    // للعامة - عرض فعالية محددة
    public function showPublic(Event $event)
    {
        if ($event->status !== 'published') {
            return response()->json(['message' => 'الفعالية غير متاحة'], 404);
        }

        return new EventResource($event->load(['organizer']));
    }
}