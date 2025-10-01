<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AdminController extends Controller
{
    public function dashboardStats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_organizers' => User::where('role', 'organizer')->count(),
            'total_attendees' => User::where('role', 'attendee')->count(),
            'total_events' => Event::count(),
            'published_events' => Event::where('status', 'published')->count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('payment_status', 'completed')->sum('total_amount'),
            'pending_orders' => Order::where('payment_status', 'pending')->count(),
        ];

        return response()->json($stats);
    }

    // في AdminController.php
public function monthlyRevenue(Request $request)
{
    $request->validate([
        'year' => 'nullable|integer|min:2020|max:' . date('Y'),
        'months' => 'nullable|integer|min:1|max:12'
    ]);

    $year = $request->get('year', date('Y'));
    $months = $request->get('months', 6);
    
    // الحصول على الأشهر المطلوبة
    $startMonth = date('m') - $months + 1;
    $startYear = $year;
    if ($startMonth < 1) {
        $startMonth += 12;
        $startYear--;
    }

    $revenueData = Order::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('YEAR(created_at) as year'),
            DB::raw('COALESCE(SUM(total_amount), 0) as total')
        )
        ->where('payment_status', 'completed')
        ->where(function($query) use ($startYear, $startMonth, $year, $months) {
            $query->whereYear('created_at', '>=', $startYear)
                  ->whereMonth('created_at', '>=', $startMonth)
                  ->orWhereYear('created_at', '>', $startYear);
        })
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();

    // ملء الأشهر الفارغة
    $monthsArabic = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                    'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    
    $formattedData = [];
    for ($i = 0; $i < $months; $i++) {
        $currentMonth = (date('m') - $i + 11) % 12 + 1;
        $currentYear = date('Y') - floor((date('m') - $i - 1) / 12);
        
        $found = $revenueData->first(function($item) use ($currentMonth, $currentYear) {
            return $item->month == $currentMonth && $item->year == $currentYear;
        });
        
        $formattedData[] = [
            'month' => $monthsArabic[$currentMonth - 1],
            'year' => $currentYear,
            'total' => $found ? $found->total : 0
        ];
    }

    return response()->json([
        'revenue_data' => array_reverse($formattedData),
        'year' => $year,
        'total_revenue' => $revenueData->sum('total')
    ]);
}

public function recentEvents(Request $request)
{
    $limit = $request->get('limit', 5);
    
    $events = Event::with('organizer')
        ->latest()
        ->take($limit)
        ->get();
    
    return response()->json($events);
}

public function recentOrders(Request $request)
{
    $limit = $request->get('limit', 5);
    
    $orders = Order::with('user')
        ->latest()
        ->take($limit)
        ->get();
    
    return response()->json($orders);
}



}