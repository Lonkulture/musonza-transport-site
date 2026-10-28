<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function page(string $page)
    {
        abort_unless(in_array($page, ['about', 'services', 'contact', 'privacy']), 404);

        return view($page);
    }

    public function service(string $service)
    {
        abort_unless(isset(config('company.services')[$service]), 404);

        return view('service', ['service' => config('company.services')[$service], 'slug' => $service]);
    }

    public function form(Request $r, string $type)
    {
        abort_unless(in_array($type, ['quote', 'consultation']), 404);
        if (! $r->session()->has('request_token')) {
            $r->session()->put('request_token', Str::random(40));
        }

        return response()->view('request', ['type' => $type])->header('Cache-Control', 'private, no-store');
    }

    public function submit(Request $r, string $type)
    {
        abort_unless(in_array($type, ['quote', 'consultation']), 404);
        if ($r->filled('website')) {
            return back()->withErrors(['form' => 'Please leave the website field empty.']);
        }
        $d = $r->validate(['request_token' => 'required|string|size:40', 'name' => 'required|string|max:120', 'email' => 'required|email|max:254', 'phone' => ['required', 'regex:/^\+?[0-9 ()-]{9,24}$/'], 'company' => 'nullable|string|max:160', 'service' => 'required|in:'.implode(',', array_keys(config('company.services'))), 'message' => 'required|string|min:10|max:4000', 'origin' => 'nullable|string|max:200', 'destination' => 'nullable|string|max:200', 'preferred_at' => ($type === 'consultation' ? 'required' : 'nullable').'|date|after:now', 'consent' => 'accepted']);
        if (! $r->session()->has('request_owner')) {
            $r->session()->put('request_owner', Str::random(40));
        }
        $key = hash('sha256', $r->session()->get('request_owner').':'.$d['request_token']);
        $prior = Enquiry::where('request_key', $key)->first();
        if ($prior) {
            return redirect('/thank-you')->with('reference', $prior->reference);
        }
        abort_unless(hash_equals((string) $r->session()->get('request_token'), $d['request_token']), 419);
        $slot = null;
        if ($type === 'consultation') {
            $slot = Carbon::parse($d['preferred_at'], 'Africa/Lusaka');
            if ($slot->isWeekend() || $slot->hour < 9 || $slot->hour > 16 || ! in_array($slot->minute, [0, 30], true) || $slot->second !== 0) {
                return back()->withInput()->withErrors(['preferred_at' => 'Choose a weekday slot from 09:00 to 16:30 CAT, on the hour or half hour.']);
            }
        }
        unset($d['request_token'],$d['consent']);
        $d['preferred_at'] = $slot;
        $b = DB::transaction(function () use ($d, $key, $type) {
            $e = Enquiry::create($d + ['type' => $type, 'request_key' => $key, 'reference' => 'MTC-'.strtoupper(Str::random(10))]);
            DB::table('enquiry_events')->insert(['enquiry_id' => $e->id, 'status' => 'new', 'created_at' => now()]);

            return $e;
        });
        $r->session()->forget('request_token');

        return redirect('/thank-you')->with('reference', $b->reference);
    }
}
