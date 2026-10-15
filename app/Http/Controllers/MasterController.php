<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Personnel;
use App\Models\User;
use App\Services\AuditService;
use App\Support\MasterRegistry;
use App\Support\PlantAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class MasterController extends Controller
{
    private function query($module, $user)
    {
        [, $class, $fields] = MasterRegistry::get($module);
        $q = $class::query();
        if ($module === 'equipment') {
            $q->with('latestLocation');
        }
        if (in_array('plant_id', $fields)) {
            PlantAccess::query($q, $user);
        } elseif ($module === 'plants') {
            $q->whereIn('id', PlantAccess::ids($user));
        } elseif ($module === 'companies') {
            $q->whereIn('id', $user->plants()->pluck('company_id'));
        }
        return $q;
    }
    private function access($user, $module, $manage = false)
    {
        abort_unless($user->can($manage ? 'equipment.manage' : 'equipment.view'), 403);
    }
    public function index(Request $r, $module)
    {
        $this->access($r->user(), $module);
        [$title, $class, $fields] = MasterRegistry::get($module);
        $q = $this->query($module, $r->user());
        if ($r->filled('q')) {
            $q->where(function ($q) use ($r, $fields) {
                foreach (array_intersect(['name', 'code', 'personnel_number'], $fields) as $f) {
                    $q->orWhere($f, 'like', '%' . $r->q . '%');
                }
            });
        }
        $sort = in_array($r->get('sort'), $fields) ? $r->sort : 'id';
        $records = $q->orderBy($sort, $r->get('direction') === 'asc' ? 'asc' : 'desc')->paginate(15)->withQueryString();
        return \App\Support\WebPage::render('master.index', compact('module', 'title', 'fields', 'records'));
    }
    public function form(Request $r, $module, $id = null)
    {
        $this->access($r->user(), $module, true);
        [$title, $class, $fields] = MasterRegistry::get($module);
        $record = $id ? $this->query($module, $r->user())->findOrFail($id) : new $class();
        $options = [];
        foreach (MasterRegistry::foreign() as $field => $model) {
            if (!in_array($field, $fields)) {
                continue;
            }
            $q = $model::query();
            if ($field === 'user_id') {
                $q->whereHas('plants', function ($q) use ($r) { $q->whereIn('plants.id', PlantAccess::ids($r->user())); });
            }
            if ($field === 'company_id') {
                $q->whereIn('id', $r->user()->plants()->pluck('company_id'));
            }
            if ($field === 'plant_id') {
                $q->whereIn('id', PlantAccess::ids($r->user()));
            }
            if (in_array($field, ['division_id', 'default_operator_id'])) {
                PlantAccess::query($q, $r->user());
            }
            $options[$field] = $q->get();
        }
        return \App\Support\WebPage::render('master.form', compact('module', 'title', 'fields', 'record', 'options'));
    }
    public function save(Request $r, $module, $id = null)
    {
        $this->access($r->user(), $module, true);
        [$title, $class, $fields] = MasterRegistry::get($module);
        $record = $id ? $this->query($module, $r->user())->findOrFail($id) : new $class();
        $rules = [];
        $nullable = ['address', 'division_id', 'user_id', 'default_operator_id', 'registration_number', 'brand', 'model', 'year', 'capacity', 'performance_unit', 'parent_id', 'sap_reason_code'];
        foreach ($fields as $f) {
            $required = in_array($f, $nullable) ? 'nullable' : 'required';
            if (isset(MasterRegistry::foreign()[$f])) {
                $m = MasterRegistry::foreign()[$f];
                $rules[$f] = $required . '|integer|exists:' . (new $m())->getTable() . ',id';
            } elseif (in_array($f, ['active', 'productive', 'requires_photo'])) {
                $rules[$f] = 'required|boolean';
            } elseif (in_array($f, ['lower_limit', 'upper_limit', 'tariff', 'unit_cost', 'capacity'])) {
                $rules[$f] = $required . '|numeric|min:0';
            } elseif ($f === 'max_helpers') {
                $rules[$f] = 'required|integer|min:1|max:4';
            } elseif ($f === 'year') {
                $rules[$f] = 'nullable|integer|min:1900|max:2100';
            } elseif ($f === 'effective_date') {
                $rules[$f] = 'required|date';
            } elseif ($f === 'start_time') {
                $rules[$f] = 'required|date_format:H:i';
            } else {
                $rules[$f] = $required . '|string|max:255';
            }
        }
        if (in_array('meter_type', $fields)) {
            $rules['meter_type'] = 'required|in:KM,HM,HOUR_KWH';
        }
        if ($module === 'personnels') {
            $rules['type'] = 'required|in:DRIVER,OPERATOR,HELPER,MECHANIC';
        }
        if ($module === 'equipment') {
            $rules['ownership'] = 'required|in:OWN,LEND,RENT';
        }
        if ($module === 'fuel-ratios') {
            $rules['upper_limit'] .= '|gte:lower_limit';
        }
        foreach (['code', 'personnel_number'] as $f) {
            if (in_array($f, $fields)) {
                $rule = Rule::unique((new $class())->getTable(), $f)->ignore($id);
                if (in_array($module, ['locations', 'divisions'])) {
                    $rule->where('plant_id', $r->plant_id);
                }
                $rules[$f] = array_merge(explode('|', $rules[$f]), [$rule]);
            }
        }
        $data = $r->validate($rules);
        if (isset($data['plant_id'])) {
            PlantAccess::check($r->user(), $data['plant_id']);
        }
        if ($module === 'plants') {
            abort_unless(in_array((int) $data['company_id'], $r->user()->plants()->pluck('company_id')->map(function ($v) {
                return (int) $v;
            })->all()), 403);
        }
        if ($record->exists && isset($data['plant_id']) && $record->plant_id != $data['plant_id']) {
            abort(422, 'Plant master tidak dapat dipindah karena dapat memiliki histori; buat master baru.');
        }
        if (!empty($data['user_id'])) {
            $linked = User::findOrFail($data['user_id']);
            PlantAccess::check($linked, $data['plant_id']);
        }
        if (!empty($data['division_id'])) {
            Division::where('plant_id', $data['plant_id'])->findOrFail($data['division_id']);
        }
        if (!empty($data['default_operator_id'])) {
            Personnel::where('plant_id', $data['plant_id'])->whereIn('type', ['OPERATOR', 'DRIVER'])->where('active', true)->findOrFail($data['default_operator_id']);
        }
        DB::transaction(function () use ($record, $data, $module, $r) {
            $before = $record->toArray();
            $record->fill($data)->save();
            if ($module === 'plants' && !$before) {
                $r->user()->plants()->syncWithoutDetaching([$record->id]);
            }
            AuditService::record($before ? 'master.updated' : 'master.created', $record, $before, $record->toArray());
        });
        return redirect('/master/' . $module)->with('success', 'Data tersimpan.');
    }
    public function destroy(Request $r, $module, $id)
    {
        $this->access($r->user(), $module, true);
        $record = $this->query($module, $r->user())->findOrFail($id);
        DB::transaction(function () use ($record) {
            AuditService::record('master.deleted', $record, $record->toArray());
            $record->delete();
        });
        return back()->with('success', 'Master diarsipkan; histori tetap tersimpan.');
    }
}
