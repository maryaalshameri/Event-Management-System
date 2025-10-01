<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizerController extends Controller
{
    public function dashboardStats(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total_events' => Event::where('organizer_id', $user->id)->count(),
            'published_events' => Event::where('organizer_id', $user->id)->where('status', 'published')->count(),
            'draft_events' => Event::where('organizer_id', $user->id)->where('status', 'draft')->count(),
            'total_orders' => Order::whereHas('event', function($query) use ($user) {
                $query->where('organizer_id', $user->id);
            })->count(),
            'total_revenue' => Order::whereHas('event', function($query) use ($user) {
                $query->where('organizer_id', $user->id);
            })->where('payment_status', 'completed')->sum('total_amount'),
            'pending_orders' => Order::whereHas('event', function($query) use ($user) {
                $query->where('organizer_id', $user->id);
            })->where('payment_status', 'pending')->count(),
        ];

        return response()->json($stats);
    }

    public function recentEvents(Request $request)
    {
        $user = $request->user();
        $limit = $request->get('limit', 5);
        
        $events = Event::where('organizer_id', $user->id)
            ->withCount(['orders'])
            ->latest()
            ->take($limit)
            ->get();
        
        return response()->json($events);
    }

    public function recentOrders(Request $request)
    {
        $user = $request->user();
        $limit = $request->get('limit', 5);
        
        $orders = Order::whereHas('event', function($query) use ($user) {
                $query->where('organizer_id', $user->id);
            })
            ->with(['user', 'event'])
            ->latest()
            ->take($limit)
            ->get();
        
        return response()->json($orders);
    }

    // في OrganizerController.php
public function reviewStats(Request $request)
{
    $user = $request->user();
    
    // إحصائيات التقييمات
    $reviewStats = DB::table('reviews')
        ->join('events', 'reviews.event_id', '=', 'events.id')
        ->where('events.organizer_id', $user->id)
        ->select(
            DB::raw('AVG(rating) as average_rating'),
            DB::raw('COUNT(*) as total_reviews'),
            DB::raw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star_reviews'),
            DB::raw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star_reviews'),
            DB::raw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star_reviews'),
            DB::raw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star_reviews'),
            DB::raw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star_reviews')
        )
        ->first();

    // توزيع التقييمات
    $ratingDistribution = [
        5 => $reviewStats->five_star_reviews,
        4 => $reviewStats->four_star_reviews,
        3 => $reviewStats->three_star_reviews,
        2 => $reviewStats->two_star_reviews,
        1 => $reviewStats->one_star_reviews
    ];

    return response()->json([
        'average_rating' => round($reviewStats->average_rating, 1),
        'total_reviews' => $reviewStats->total_reviews,
        'five_star_reviews' => $reviewStats->five_star_reviews,
        'rating_distribution' => $ratingDistribution
    ]);
}
}