<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\FilterOptions;
use App\Support\PlantAccess;
use Illuminate\Http\Request;
class DashboardController extends Controller
{
    public function index(Request $r, DashboardService $service)
    {
        $filters = $r->validate(['from' => 'nullable|date', 'to' => 'nullable|date' . ($r->filled('from') ? '|after_or_equal:from' : ''), 'company_id' => 'nullable|integer', 'plant_id' => 'nullable|integer', 'division_id' => 'nullable|integer', 'equipment_type_id' => 'nullable|integer', 'ownership' => 'nullable|in:OWN,LEND,RENT']);
        $data = $service->data($r->user(), $filters);
        return $r->expectsJson() ? response()->json(['data' => $data]) : \App\Support\WebPage::render('dashboard', compact('data', 'filters') + ['options' => FilterOptions::forUser($r->user())]);
    }
    public function plants(Request $r)
    {
        return \App\Support\WebPage::render('select-plant', ['plants' => $r->user()->plants()->where('active', true)->get()]);
    }
    public function select(Request $r)
    {
        $r->validate(['plant_id' => 'required|integer']);
        PlantAccess::check($r->user(), $r->plant_id);
        $r->session()->put('active_plant_id', (int) $r->plant_id);
        return redirect('/dashboard');
    }
    public function notifications(Request $r)
    {
        $data = $r->user()->notifications()->latest()->paginate(20);
        return $r->expectsJson() ? response()->json($data) : view('notifications', compact('data'));
    }
    public function readNotification(Request $r, $id)
    {
        $n = $r->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        return redirect($n->data['url'] ?? '/dashboard');
    }
    public function profile()
    {
        return \App\Support\WebPage::render('profile');
    }
    public function updateProfile(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:255', 'contact' => 'nullable|string|max:100', 'current_password' => 'required_with:password|current_password', 'password' => 'nullable|confirmed|min:12']);
        $r->user()->update(collect($data)->only(['name', 'contact'])->all());
        if (!empty($data['password'])) {
            $r->user()->update(['password' => bcrypt($data['password']), 'security_version' => $r->user()->security_version + 1]);
            $r->user()->tokens()->delete();
        }
        $r->session()->put('security_version', $r->user()->security_version);
        $r->session()->regenerate();
        return back()->with('success', 'Profil diperbarui.');
    }
}
