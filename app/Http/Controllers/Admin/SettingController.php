<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'deliveryEstimate' => Setting::deliveryEstimateLabel(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'delivery_estimate' => ['required', 'string', 'max:120'],
        ]);

        Setting::putValue('delivery_estimate', [
            'label' => $validated['delivery_estimate'],
        ]);

        return redirect()->route('admin.settings.edit')->with('status', 'Impostazioni aggiornate.');
    }
}
