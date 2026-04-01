<?php

namespace App\Http\Controllers;

use App\Models\Payment;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $payments = Payment::query()
            ->latest()
            ->paginate(15);

        return view('admin.dashboard', compact('payments'));
    }
}
