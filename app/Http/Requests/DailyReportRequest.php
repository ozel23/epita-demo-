<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class DailyReportRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('daily-report.create');
    }
    public function rules()
    {
        return ['plant_id' => 'required|integer', 'equipment_id' => 'required|integer', 'main_operator_id' => 'required|integer', 'location_id' => 'required|integer', 'assignment_id' => 'nullable|integer', 'report_date' => 'required|date_format:Y-m-d', 'shift' => 'required|string|max:20', 'meter_start' => 'required|numeric|min:0|max:9999999999999', 'meter_end' => 'required|numeric|gte:meter_start|max:9999999999999', 'work_start_at' => 'required|date', 'work_end_at' => 'required|date|after:work_start_at', 'equipment_condition' => 'required|in:WORKING,STANDBY,BREAKDOWN,MAINTENANCE', 'non_moving_reason_id' => 'nullable|integer', 'description' => 'nullable|string|max:5000', 'meter_override_reason' => 'nullable|string|max:1000', 'activities' => 'required|array|min:1|max:30', 'activities.*.activity_code_id' => 'required|integer', 'activities.*.work_unit_id' => 'required|integer', 'activities.*.load_type_id' => 'nullable|integer', 'activities.*.volume' => 'required|numeric|min:0|max:9999999999999', 'activities.*.description' => 'nullable|string|max:1000', 'helpers' => 'nullable|array|max:4', 'helpers.*' => 'integer|distinct', 'fuels' => 'nullable|array|max:20', 'fuels.*.fuel_type_id' => 'required|integer', 'fuels.*.fueling_location_id' => 'nullable|integer', 'fuels.*.quantity_filled' => 'required|numeric|min:0', 'fuels.*.quantity_used' => 'required|numeric|min:0', 'fuels.*.quantity_remaining' => 'required|numeric|min:0'];
    }
}
