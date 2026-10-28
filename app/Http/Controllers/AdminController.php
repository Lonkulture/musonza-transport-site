<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string|max:200']);
        if (! Auth::attempt(['email' => strtolower(trim($d['email'])), 'password' => $d['password'], 'active' => true])) {
            return back()->withErrors(['email' => 'These sign-in details could not be verified.'])->onlyInput('email');
        }$r->session()->regenerate();
        $r->session()->put('account_version', $r->user()->session_version);

        return redirect('/admin');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/');
    }

    public function password(Request $r)
    {
        $d = $r->validate(['current_password' => 'required|current_password', 'password' => ['required', 'confirmed', 'different:current_password', Password::min(12), 'max:72', function ($a, $v, $fail) {
            if (strlen($v) > 72 || str_contains($v, "\0")) {
                $fail('Use 12 to 72 bytes without null characters.');
            }
        }]]);
        $u = $r->user();
        $u->update(['password' => $d['password'], 'must_change_password' => false, 'session_version' => $u->session_version + 1]);
        $r->session()->regenerate();
        $r->session()->put('account_version', $u->session_version);

        return redirect('/admin')->with('notice', 'Password changed. Other sessions have been revoked.');
    }

    public function index(Request $r)
    {
        $q = Enquiry::query();
        if (in_array($r->query('status'), ['new', 'reviewing', 'confirmed', 'closed'], true)) {
            $q->where('status', $r->query('status'));
        }if (in_array($r->query('type'), ['quote', 'consultation'], true)) {
            $q->where('type', $r->query('type'));
        }

return view('admin', ['enquiries' => $q->latest()->paginate(15)->withQueryString(), 'counts' => Enquiry::selectRaw('status,count(*) as total')->groupBy('status')->pluck('total', 'status')]);
    }

    public function show(Enquiry $enquiry)
    {
        return view('admin-detail', ['enquiry' => $enquiry, 'events' => DB::table('enquiry_events')->where('enquiry_id', $enquiry->id)->orderBy('id')->get()]);
    }

    public function update(Request $r, Enquiry $enquiry)
    {
        $d = $r->validate(['status' => 'required|in:new,reviewing,confirmed,closed', 'internal_note' => 'required|string|min:3|max:2000']);
        try {
            DB::transaction(function () use ($enquiry, $d, $r) {
                $e = Enquiry::lockForUpdate()->findOrFail($enquiry->id);
                $slot = null;
                if ($d['status'] === 'confirmed' && $e->type === 'consultation') {
                    abort_if(! $e->preferred_at || $e->preferred_at->isPast(), 422, 'Choose a new appointment request; the requested time has passed.');
                    $slot = $e->preferred_at->format('Y-m-d H:i');
                    abort_if(Enquiry::where('confirmed_slot', $slot)->where('id', '!=', $e->id)->exists(), 409, 'This consultation slot is already confirmed.');
                }$e->update($d + ['confirmed_slot' => $slot]);
                DB::table('enquiry_events')->insert(['enquiry_id' => $e->id, 'user_id' => $r->user()->id, 'status' => $d['status'], 'note' => encrypt($d['internal_note']), 'created_at' => now()]);
            }, 5);
        } catch (QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23505'], true)) {
                abort(409, 'This consultation slot is already confirmed.');
            }throw $e;
        }

        return back()->with('notice', 'Saved. Contact the client directly to communicate this update.');
    }

    public function settings(Request $r)
    {
        $d = $r->validate(['phone' => ['required', 'regex:/^\+?[0-9 ()-]{9,24}$/'], 'email' => 'required|email|max:254', 'office' => 'required|string|max:200', 'hero_heading' => 'required|string|max:120', 'hero_intro' => 'required|string|max:400']);
        DB::transaction(function () use ($d) {
            foreach ($d as $key => $value) {
                DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
            }
        });

        return back()->with('notice','Public company details updated.');
    }
}
