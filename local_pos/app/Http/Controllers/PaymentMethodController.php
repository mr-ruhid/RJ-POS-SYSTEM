<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::all();
        return view('admin.settings.payments', compact('methods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'type' => 'required|in:cash,card,other',
        ]);

        PaymentMethod::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'type' => $request->type,
            'is_integrated' => $request->has('is_integrated'),
            'driver_name' => $request->driver_name, // pax, verifone, ingenico
            'settings' => [
                'ip' => $request->ip_address,
                'port' => $request->port,
                'com_port' => $request->com_port
            ],
            'is_active' => true
        ]);

        return back()->with('success', 'Ödəniş üsulu əlavə edildi.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        // Nəğd silinə bilməz
        if ($paymentMethod->slug == 'cash') {
            return back()->with('error', 'Nəğd ödəniş növü silinə bilməz.');
        }
        $paymentMethod->delete();
        return back()->with('success', 'Silindi.');
    }

    public function toggle(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['is_active' => !$paymentMethod->is_active]);
        return back()->with('success', 'Status dəyişdirildi.');
    }
}
