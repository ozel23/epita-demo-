<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request)
    {
        return is_file(public_path('assets/react/inertia.js')) ? hash_file('sha256',public_path('assets/react/inertia.js')) : null;
    }
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => function () use ($request) {
                $user = $request->user();
                return ['user' => $user ? $user->only('id', 'name', 'username', 'contact', 'personnel_number') : null,
                    'plants' => $user ? $user->plants()->where('active', true)->get(['plants.id','code','name']) : [],
                    'roles' => $user ? $user->getRoleNames() : [],
                    'activePlant' => $request->session()->get('active_plant_id'),
                    'permissions' => $user ? $user->getAllPermissions()->pluck('name') : []];
            },
            'masterModules' => collect(\App\Support\MasterRegistry::all())->map(function ($module) { return $module[0]; })->all(),
            'reportTypes' => \App\Services\ReportService::TYPES,
            'flash' => function () use ($request) {
                return ['success' => $request->session()->get('success') ?: $request->session()->get('status')];
            },
        ]);
    }
}
