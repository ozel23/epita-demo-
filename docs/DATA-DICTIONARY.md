# Data dictionary

Generated from migrated local schema. MySQL operational quantities use DECIMAL(15,2), fuel ratios DECIMAL(15,4). Foreign keys restrict deletion; master records use deleted_at for history retention. Datetimes use Asia/Jakarta consistently.

## activity_codes

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| active | tinyint(1) | No | '1' |
| parent_id | INTEGER | Yes | — |
| sap_reason_code | varchar | Yes | — |
| productive | tinyint(1) | No | '1' |
| requires_photo | tinyint(1) | No | '0' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

## approval_histories

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| daily_report_id | INTEGER | No | — |
| actor_id | INTEGER | No | — |
| from_status | varchar | Yes | — |
| to_status | varchar | Yes | — |
| revision_no | INTEGER | No | — |
| note | TEXT | Yes | — |
| snapshot | TEXT | Yes | — |
| ip | varchar | Yes | — |
| user_agent | TEXT | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: actor_id → users.id, daily_report_id → daily_reports.id.

## assignment_personnels

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| assignment_id | INTEGER | No | — |
| personnel_id | INTEGER | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: personnel_id → personnels.id, assignment_id → assignments.id.

## assignments

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| work_plan_id | INTEGER | Yes | — |
| equipment_id | INTEGER | No | — |
| operator_id | INTEGER | No | — |
| created_by | INTEGER | No | — |
| planned_start_at | datetime | No | — |
| planned_end_at | datetime | No | — |
| shift | varchar | No | '1' |
| status | varchar | No | 'ASSIGNED' |
| override_reason | TEXT | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: created_by → users.id, operator_id → personnels.id, equipment_id → equipment.id, work_plan_id → work_plans.id, plant_id → plants.id.

## audit_logs

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| user_id | INTEGER | Yes | — |
| plant_id | INTEGER | Yes | — |
| event | varchar | Yes | — |
| subject_type | varchar | Yes | — |
| subject_id | INTEGER | Yes | — |
| before | TEXT | Yes | — |
| after | TEXT | Yes | — |
| ip | varchar | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: plant_id → plants.id, user_id → users.id.

## background_tasks

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| user_id | INTEGER | No | — |
| plant_id | INTEGER | Yes | — |
| type | varchar | No | — |
| status | varchar | No | 'QUEUED' |
| parameters | TEXT | No | — |
| path | varchar | Yes | — |
| error | TEXT | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: plant_id → plants.id, user_id → users.id.

## breakdown_attachments

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| breakdown_report_id | INTEGER | No | — |
| uploaded_by | INTEGER | No | — |
| path | varchar | Yes | — |
| mime_type | varchar | Yes | — |
| size | INTEGER | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| thumbnail_path | varchar | Yes | — |

Foreign keys: uploaded_by → users.id, breakdown_report_id → breakdown_reports.id.

## breakdown_reports

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| equipment_id | INTEGER | No | — |
| location_id | INTEGER | No | — |
| reported_by | INTEGER | No | — |
| number | varchar | No | — |
| occurred_at | datetime | No | — |
| meter | numeric | No | '0' |
| symptom | varchar | Yes | — |
| severity | varchar | No | 'MEDIUM' |
| status | varchar | No | 'REPORTED' |
| description | TEXT | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: reported_by → users.id, location_id → locations.id, equipment_id → equipment.id, plant_id → plants.id.

## companies

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| address | TEXT | Yes | — |
| active | tinyint(1) | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

## daily_report_activities

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| daily_report_id | INTEGER | No | — |
| activity_code_id | INTEGER | No | — |
| load_type_id | INTEGER | Yes | — |
| work_unit_id | INTEGER | No | — |
| volume | numeric | No | '0' |
| description | TEXT | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: work_unit_id → work_units.id, load_type_id → load_types.id, activity_code_id → activity_codes.id, daily_report_id → daily_reports.id.

## daily_report_attachments

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| daily_report_id | INTEGER | No | — |
| uploaded_by | INTEGER | No | — |
| path | varchar | Yes | — |
| mime_type | varchar | Yes | — |
| size | INTEGER | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| thumbnail_path | varchar | Yes | — |

Foreign keys: uploaded_by → users.id, daily_report_id → daily_reports.id.

## daily_report_fuels

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| daily_report_id | INTEGER | No | — |
| fuel_type_id | INTEGER | No | — |
| fueling_location_id | INTEGER | Yes | — |
| quantity_filled | numeric | No | '0' |
| quantity_used | numeric | No | '0' |
| quantity_remaining | numeric | No | '0' |
| ratio | numeric | Yes | — |
| abnormal | tinyint(1) | No | '0' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: fueling_location_id → fueling_locations.id, fuel_type_id → fuel_types.id, daily_report_id → daily_reports.id.

## daily_report_personnels

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| daily_report_id | INTEGER | No | — |
| personnel_id | INTEGER | No | — |
| role | varchar | No | 'HELPER' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: personnel_id → personnels.id, daily_report_id → daily_reports.id.

## daily_reports

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| company_id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| division_id | INTEGER | Yes | — |
| location_id | INTEGER | No | — |
| assignment_id | INTEGER | Yes | — |
| equipment_id | INTEGER | No | — |
| main_operator_id | INTEGER | No | — |
| non_moving_reason_id | INTEGER | Yes | — |
| report_number | varchar | No | — |
| report_date | date | No | — |
| shift | varchar | No | '1' |
| meter_type | varchar | No | 'KM' |
| meter_start | numeric | No | '0' |
| meter_end | numeric | No | '0' |
| meter_total | numeric | No | '0' |
| work_start_at | datetime | No | — |
| work_end_at | datetime | No | — |
| work_minutes | INTEGER | No | — |
| equipment_condition | varchar | No | 'WORKING' |
| description | TEXT | Yes | — |
| meter_override_reason | TEXT | Yes | — |
| status | varchar | No | 'DRAFT' |
| revision_no | INTEGER | No | '0' |
| submitted_at | datetime | Yes | — |
| verified_at | datetime | Yes | — |
| approved_at | datetime | Yes | — |
| verified_by | INTEGER | Yes | — |
| approved_by | INTEGER | Yes | — |
| created_by | INTEGER | No | — |
| updated_by | INTEGER | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: updated_by → users.id, created_by → users.id, approved_by → users.id, verified_by → users.id, non_moving_reason_id → non_moving_reasons.id, main_operator_id → personnels.id, equipment_id → equipment.id, assignment_id → assignments.id, location_id → locations.id, division_id → divisions.id, plant_id → plants.id, company_id → companies.id.

## divisions

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| active | tinyint(1) | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

Foreign keys: plant_id → plants.id.

## equipment

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| equipment_type_id | INTEGER | No | — |
| default_operator_id | INTEGER | Yes | — |
| code | varchar | No | — |
| name | varchar | No | — |
| registration_number | varchar | Yes | — |
| brand | varchar | Yes | — |
| model | varchar | Yes | — |
| performance_unit | varchar | Yes | — |
| year | INTEGER | Yes | — |
| capacity | numeric | No | '0' |
| last_meter | numeric | No | '0' |
| ownership | varchar | No | 'OWN' |
| meter_type | varchar | No | 'KM' |
| current_condition | varchar | No | 'STANDBY' |
| active | tinyint(1) | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

Foreign keys: default_operator_id → personnels.id, equipment_type_id → equipment_types.id, plant_id → plants.id.

## equipment_location_histories

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| equipment_id | INTEGER | No | — |
| actor_id | INTEGER | No | — |
| reason | TEXT | No | — |
| location_id | INTEGER | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: location_id → locations.id, actor_id → users.id, equipment_id → equipment.id.

## equipment_status_histories

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| equipment_id | INTEGER | No | — |
| actor_id | INTEGER | No | — |
| reason | TEXT | No | — |
| from_condition | varchar | Yes | — |
| to_condition | varchar | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: actor_id → users.id, equipment_id → equipment.id.

## equipment_types

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| active | tinyint(1) | No | '1' |
| prefix | varchar | Yes | — |
| meter_type | varchar | No | 'KM' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

## failed_jobs

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| uuid | varchar | No | — |
| connection | TEXT | No | — |
| queue | TEXT | No | — |
| payload | TEXT | No | — |
| exception | TEXT | No | — |
| failed_at | datetime | No | CURRENT_TIMESTAMP |

## fuel_ratio_standards

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| equipment_type_id | INTEGER | No | — |
| lower_limit | numeric | No | '0' |
| upper_limit | numeric | No | '0' |
| effective_date | date | No | — |
| unit | varchar | No | 'liter/km' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

Foreign keys: equipment_type_id → equipment_types.id.

## fuel_types

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| active | tinyint(1) | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

## fueling_locations

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| active | tinyint(1) | No | '1' |
| plant_id | INTEGER | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

Foreign keys: plant_id → plants.id.

## jobs

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| queue | varchar | No | — |
| payload | TEXT | No | — |
| attempts | INTEGER | No | — |
| reserved_at | INTEGER | Yes | — |
| available_at | INTEGER | No | — |
| created_at | INTEGER | No | — |

## load_types

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| active | tinyint(1) | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

## locations

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| division_id | INTEGER | Yes | — |
| code | varchar | No | — |
| name | varchar | No | — |
| location_type | varchar | No | 'OP' |
| active | tinyint(1) | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

Foreign keys: division_id → divisions.id, plant_id → plants.id.

## maintenance_actions

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| maintenance_work_order_id | INTEGER | No | — |
| technician_id | INTEGER | No | — |
| description | TEXT | No | — |
| labor_minutes | INTEGER | No | '0' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: technician_id → personnels.id, maintenance_work_order_id → maintenance_work_orders.id.

## maintenance_attachments

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| maintenance_work_order_id | INTEGER | No | — |
| uploaded_by | INTEGER | No | — |
| path | varchar | No | — |
| thumbnail_path | varchar | Yes | — |
| mime_type | varchar | No | — |
| size | INTEGER | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: uploaded_by → users.id, maintenance_work_order_id → maintenance_work_orders.id.

## maintenance_part_usages

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| maintenance_work_order_id | INTEGER | No | — |
| spare_part_id | INTEGER | No | — |
| quantity | numeric | No | '0' |
| unit_cost | numeric | No | '0' |
| warehouse_reference | varchar | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: spare_part_id → spare_parts.id, maintenance_work_order_id → maintenance_work_orders.id.

## maintenance_technicians

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| maintenance_work_order_id | INTEGER | No | — |
| personnel_id | INTEGER | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: personnel_id → personnels.id, maintenance_work_order_id → maintenance_work_orders.id.

## maintenance_work_orders

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| equipment_id | INTEGER | No | — |
| breakdown_report_id | INTEGER | Yes | — |
| created_by | INTEGER | No | — |
| closed_by | INTEGER | Yes | — |
| number | varchar | No | — |
| sap_order_number | varchar | Yes | — |
| type | varchar | No | 'CORRECTIVE' |
| priority | varchar | No | 'MEDIUM' |
| status | varchar | No | 'OPEN' |
| diagnosis | TEXT | Yes | — |
| result | TEXT | Yes | — |
| unavailable_at | datetime | Yes | — |
| started_at | datetime | Yes | — |
| ended_at | datetime | Yes | — |
| released_at | datetime | Yes | — |
| downtime_minutes | INTEGER | No | '0' |
| next_service_date | date | Yes | — |
| next_service_meter | numeric | No | '0' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: closed_by → users.id, created_by → users.id, breakdown_report_id → breakdown_reports.id, equipment_id → equipment.id, plant_id → plants.id.

## migrations

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| migration | varchar | No | — |
| batch | INTEGER | No | — |

## model_has_permissions

| Field | Type | Nullable | Default |
|---|---|---|---|
| permission_id | INTEGER | No | — |
| model_type | varchar | No | — |
| model_id | INTEGER | No | — |

Foreign keys: permission_id → permissions.id.

## model_has_roles

| Field | Type | Nullable | Default |
|---|---|---|---|
| role_id | INTEGER | No | — |
| model_type | varchar | No | — |
| model_id | INTEGER | No | — |

Foreign keys: role_id → roles.id.

## non_moving_reasons

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| active | tinyint(1) | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

## notifications

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | varchar | No | — |
| type | varchar | No | — |
| notifiable_type | varchar | No | — |
| notifiable_id | INTEGER | No | — |
| data | TEXT | No | — |
| read_at | datetime | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

## overtime_rules

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| start_time | time | No | — |
| tariff | numeric | No | '0' |
| max_helpers | INTEGER | No | '4' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

Foreign keys: plant_id → plants.id.

## password_resets

| Field | Type | Nullable | Default |
|---|---|---|---|
| email | varchar | No | — |
| token | varchar | No | — |
| created_at | datetime | Yes | — |

## permissions

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| name | varchar | No | — |
| guard_name | varchar | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

## personal_access_tokens

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| tokenable_type | varchar | No | — |
| tokenable_id | INTEGER | No | — |
| name | varchar | No | — |
| token | varchar | No | — |
| abilities | TEXT | Yes | — |
| last_used_at | datetime | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| security_version | INTEGER | No | '1' |

## personnels

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| user_id | INTEGER | Yes | — |
| personnel_number | varchar | No | — |
| name | varchar | No | — |
| type | varchar | No | 'OPERATOR' |
| active | tinyint(1) | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

Foreign keys: user_id → users.id, plant_id → plants.id.

## plant_user

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| user_id | INTEGER | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: user_id → users.id, plant_id → plants.id.

## plants

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| company_id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| active | tinyint(1) | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

Foreign keys: company_id → companies.id.

## role_has_permissions

| Field | Type | Nullable | Default |
|---|---|---|---|
| permission_id | INTEGER | No | — |
| role_id | INTEGER | No | — |

Foreign keys: role_id → roles.id, permission_id → permissions.id.

## roles

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| name | varchar | No | — |
| guard_name | varchar | No | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

## sap_import_batches

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| created_by | INTEGER | No | — |
| filename | varchar | Yes | — |
| status | varchar | No | 'STAGED' |
| row_count | INTEGER | No | '0' |
| error_count | INTEGER | No | '0' |
| mapping | TEXT | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: created_by → users.id, plant_id → plants.id.

## sap_iw47_staging_rows

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| sap_import_batch_id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| row_number | INTEGER | No | — |
| raw_data | TEXT | No | — |
| normalized_data | TEXT | Yes | — |
| errors | TEXT | Yes | — |
| equipment_code | varchar | Yes | — |
| confirmation_number | varchar | Yes | — |
| confirmation_counter | varchar | Yes | — |
| posting_date | date | Yes | — |
| actual_work | numeric | Yes | — |
| actual_work_unit | varchar | Yes | — |
| status | varchar | No | 'VALID' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: plant_id → plants.id, sap_import_batch_id → sap_import_batches.id.

## sap_mapping_rules

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| activity_code_id | INTEGER | No | — |
| order_number | varchar | Yes | — |
| operation_activity | varchar | No | '0010' |
| work_center | varchar | Yes | — |
| actual_work_unit | varchar | No | 'KM' |
| value_source | varchar | No | 'meter_total' |
| tolerance | numeric | No | '0' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: activity_code_id → activity_codes.id, plant_id → plants.id.

## sap_reconciliations

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| sap_import_batch_id | INTEGER | No | — |
| sap_iw47_staging_row_id | INTEGER | Yes | — |
| daily_report_id | INTEGER | Yes | — |
| status | varchar | Yes | — |
| local_value | numeric | Yes | — |
| sap_value | numeric | Yes | — |
| variance | numeric | Yes | — |
| reason | TEXT | Yes | — |
| reviewed_by | INTEGER | Yes | — |
| reviewed_at | datetime | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: reviewed_by → users.id, daily_report_id → daily_reports.id, sap_iw47_staging_row_id → sap_iw47_staging_rows.id, sap_import_batch_id → sap_import_batches.id, plant_id → plants.id.

## spare_parts

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| active | tinyint(1) | No | '1' |
| unit | varchar | No | 'pcs' |
| unit_cost | numeric | No | '0' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |

## users

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| name | varchar | No | — |
| email | varchar | No | — |
| email_verified_at | datetime | Yes | — |
| password | varchar | No | — |
| remember_token | varchar | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| username | varchar | Yes | — |
| active | tinyint(1) | No | '1' |
| personnel_number | varchar | Yes | — |
| contact | varchar | Yes | — |
| security_version | INTEGER | No | '1' |

## work_plan_items

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| work_plan_id | INTEGER | No | — |
| activity_code_id | INTEGER | No | — |
| work_unit_id | INTEGER | No | — |
| target_volume | numeric | No | '0' |
| equipment_requirement | INTEGER | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: work_unit_id → work_units.id, activity_code_id → activity_codes.id, work_plan_id → work_plans.id.

## work_plans

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| company_id | INTEGER | No | — |
| plant_id | INTEGER | No | — |
| division_id | INTEGER | Yes | — |
| location_id | INTEGER | Yes | — |
| created_by | INTEGER | No | — |
| number | varchar | No | — |
| start_date | date | No | — |
| end_date | date | No | — |
| status | varchar | No | 'DRAFT' |
| notes | TEXT | Yes | — |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |

Foreign keys: created_by → users.id, location_id → locations.id, division_id → divisions.id, plant_id → plants.id, company_id → companies.id.

## work_units

| Field | Type | Nullable | Default |
|---|---|---|---|
| id | INTEGER | No | — |
| code | varchar | No | — |
| name | varchar | No | — |
| active | tinyint(1) | No | '1' |
| created_at | datetime | Yes | — |
| updated_at | datetime | Yes | — |
| deleted_at | datetime | Yes | — |


## Tambahan mobile / React — 15 Oktober 2026
- daily_reports.client_uuid: UUID unik untuk create/retry offline; SQL Server memakai filtered unique index untuk nullable UUID.
- daily_reports.server_version: integer optimistic version, bertambah pada perubahan laporan termasuk transisi status.
- breakdown_reports.client_uuid: UUID unik untuk breakdown offline; deduplikasi request juga memakai receipt.
- sync_receipts: user_id + request_key unik, request_hash SHA-256, response JSON, status_code, timestamps. Replay immutable, akses tetap diperiksa ulang.
- sync_changes: bigint event id, daily_report_id, plant_id, owner_id, server_version, timestamps. Delta memakai event id, tidak hanya timestamp laporan.
- sync_acknowledgements: user_id + device_id unik, encrypted cursor terakhir, timestamps.
- SQLite mobile: setiap tabel mempunyai client_uuid (key lokal), owner_id, payload JSON, local_updated_at, server_updated_at, sync_status, sync_attempts, last_sync_error, next_attempt_at. user_version=1. Rincian aktivitas/BBM menggunakan key parent UUID + indeks; token/password tidak ada di schema.
