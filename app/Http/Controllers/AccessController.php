<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use App\Support\PlantAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class AccessController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->can('user.manage'), 403);
        $users = User::whereHas('plants', function ($q) use ($r) {
            $q->whereIn('plants.id', PlantAccess::ids($r->user()));
        })->with('roles', 'plants')->paginate(20);
        $roles = Role::all();
        return \App\Support\WebPage::render('users', compact('users', 'roles'));
    }
    public function save(Request $r)
    {
        abort_unless($r->user()->can('user.manage'), 403);
        $d = $r->validate(['name' => 'required|string|max:255', 'username' => 'required|string|max:100|unique:users', 'email' => ['required', 'email', 'unique:users', 'not_regex:/[\r\n]/'], 'password' => 'required|string|min:12', 'role' => 'required|exists:roles,name', 'plants' => 'required|array|min:1', 'plants.*' => 'required|integer']);
        foreach ($d['plants'] as $id) {
            PlantAccess::check($r->user(), $id);
        }
        $role = Role::findByName($d['role']);
        abort_unless($r->user()->can('role.assign'), 403, 'Izin penugasan role diperlukan.');
        DB::transaction(function () use ($d) {
            $u = User::create(['name' => $d['name'], 'username' => $d['username'], 'email' => $d['email'], 'password' => bcrypt($d['password']), 'active' => true]);
            $u->plants()->sync($d['plants']);
            $u->assignRole($d['role']);
            AuditService::record('user.created', null, [], ['user_id' => $u->id, 'role' => $d['role'], 'plants' => $d['plants']]);
        });
        return back()->with('success', 'Pengguna ditambahkan.');
    }
    public function toggle(Request $r, User $user)
    {
        abort_unless($r->user()->can('user.manage'), 403);
        abort_if($user->id === $r->user()->id, 422, 'Tidak dapat menonaktifkan akun sendiri.');
        $ids = $user->plants()->pluck('plants.id')->all();
        abort_unless($ids && !array_diff($ids, PlantAccess::ids($r->user())), 403);
        $user->update(['active' => !$user->active, 'security_version' => $user->security_version + 1]);
        $user->tokens()->delete();
        AuditService::record('user.status', null, [], ['user_id' => $user->id, 'active' => $user->active]);
        return back()->with('success', 'Status akun diperbarui.');
    }
    public function roles(Request $r)
    {
        abort_unless($r->user()->can('user.manage'), 403);
        return \App\Support\WebPage::render('roles', ['roles' => Role::with('permissions')->get(), 'permissions' => Permission::all()]);
    }
}
