<?php

namespace App\Http\Controllers;

use App\Models\ActivityCode;
use App\Models\Assignment;
use App\Models\Equipment;
use App\Models\FuelType;
use App\Models\Location;
use App\Models\Personnel;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\AuditService;
use App\Support\PlantAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class ApiController extends Controller
{
    public function login(Request $r)
    {
        $d = $r->validate(['login' => 'required|string', 'password' => 'required|string', 'device_name' => 'required|string|max:100']);
        $field = filter_var($d['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $u = User::where($field, $d['login'])->first();
        if (!$u || !$u->active || !Hash::check($d['password'], $u->password)) {
            AuditService::record('api.auth.failed');
            throw ValidationException::withMessages(['login' => 'Identitas atau kata sandi tidak sesuai.']);
        }
        $token = $u->createToken($d['device_name']);
        $token->accessToken->forceFill(['security_version' => $u->security_version])->save();
        return response()->json(['data' => ['token' => $token->plainTextToken, 'user' => $u]]);
    }
    public function logout(Request $r)
    {
        if ($r->user()->currentAccessToken()) {
            $r->user()->currentAccessToken()->delete();
        }
        return response()->json(['data' => ['message' => 'Logout berhasil.']]);
    }
    public function me(Request $r)
    {
        return response()->json(['data' => $r->user()->load('roles', 'plants')]);
    }
    public function equipment(Request $r)
    {
        abort_unless($r->user()->can('equipment.view'), 403);
        return response()->json(PlantAccess::query(Equipment::where('active', true), $r->user())->paginate(20));
    }
    public function assignments(Request $r)
    {
        $q = PlantAccess::query(Assignment::with('equipment', 'operator'), $r->user());
        if (!$r->user()->can('assignment.manage')) {
            $q->whereHas('operator', function ($q) use ($r) {
                $q->where('user_id', $r->user()->id);
            });
        }
        return response()->json($q->latest()->paginate(20));
    }
    public function master(Request $r)
    {
        return response()->json(['data' => ['plants' => $r->user()->plants()->where('active', true)->get(), 'personnels' => PlantAccess::query(Personnel::where('active', true), $r->user())->get(), 'locations' => PlantAccess::query(Location::where('active', true), $r->user())->get(), 'activities' => ActivityCode::where('active', true)->get(), 'units' => WorkUnit::where('active', true)->get(), 'fuels' => FuelType::where('active', true)->get(), 'fueling_locations' => PlantAccess::query(\App\Models\FuelingLocation::where('active', true), $r->user())->get(), 'load_types' => \App\Models\LoadType::where('active', true)->get(), 'non_moving_reasons' => \App\Models\NonMovingReason::where('active', true)->get()]]);
    }
}
