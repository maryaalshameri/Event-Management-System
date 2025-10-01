<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with(['organizer', 'ticketTypes', 'orders'])
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
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'start_date');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // تحديد عدد النتائج
        $limit = $request->get('limit');
        if ($limit) {
            $events = $query->take($limit)->get();
        } else {
            $events = $query->paginate($request->get('per_page', 15));
        }

        return response()->json([
            'data' => $events->items() ?? $events,
            'meta' => $limit ? null : [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ]
        ]);
    }

    public function show(Event $event)
    {
        $event->load([
            'organizer', 
            'ticketTypes', 
            'orders.user', 
            'reviews.user'
        ]);

        return response()->json($event);
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
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
            'image' => 'nullable|string'
        ]);

        $event->update($validated);

        return response()->json([
            'message' => 'تم تحديث الفعالية بنجاح',
            'event' => $event
        ]);
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json([
            'message' => 'تم حذف الفعالية بنجاح'
        ]);
    }

    public function updateStatus(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:draft,published,cancelled,completed'
        ]);

        $event->update(['status' => $request->status]);

        return response()->json([
            'message' => 'تم تحديث حالة الفعالية بنجاح',
            'event' => $event
        ]);
    }
}