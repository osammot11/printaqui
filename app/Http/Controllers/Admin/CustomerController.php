<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index()
    {
        return view('admin.customers.index', [
            'customers' => Customer::withCount('orders')->latest()->paginate(30),
        ]);
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'first_name', 'last_name', 'phone', 'orders']);

            Customer::withCount('orders')->orderBy('email')->chunk(100, function ($customers) use ($handle) {
                foreach ($customers as $customer) {
                    fputcsv($handle, [
                        $customer->email,
                        $customer->first_name,
                        $customer->last_name,
                        $customer->phone,
                        $customer->orders_count,
                    ]);
                }
            });

            fclose($handle);
        }, 'printaqui-clienti.csv');
    }
}
