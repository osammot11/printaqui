<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $orders = Order::latest()->take(8)->with('customer')->get();
        $revenueOrders = Order::whereIn('payment_status', ['paid', 'refunded']);
        $revenueOrderCount = (clone $revenueOrders)->count();
        $grossSales = (clone $revenueOrders)->sum('total_cents');
        $refunded = (clone $revenueOrders)->sum('refunded_cents');
        $netSales = max(0, $grossSales - $refunded);

        return view('admin.dashboard', [
            'orders' => $orders,
            'customerCount' => Customer::count(),
            'productCount' => Product::count(),
            'orderCount' => Order::count(),
            'paidOrderCount' => Order::where('payment_status', 'paid')->count(),
            'revenueOrderCount' => $revenueOrderCount,
            'unfulfilledPaidCount' => Order::where('payment_status', 'paid')
                ->whereNotIn('status', ['fulfilled', 'cancelled'])
                ->count(),
            'pendingPaymentCount' => Order::where('payment_status', 'pending')->count(),
            'grossSales' => $grossSales,
            'refunded' => $refunded,
            'netSales' => $netSales,
            'aov' => $revenueOrderCount > 0 ? (int) round($netSales / $revenueOrderCount) : 0,
        ]);
    }
}
