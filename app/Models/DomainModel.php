<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
/** Normalize foreign keys consistently across PHP 7 PDO MySQL/SQLite and PHP 8 drivers. */
abstract class DomainModel extends Model
{
    public function getCasts()
    {
        $casts = parent::getCasts();
        foreach (array_keys($this->attributes) as $field) {
            if (substr($field, -3) === '_id' || in_array($field, ['created_by', 'updated_by', 'reported_by', 'uploaded_by', 'approved_by', 'verified_by', 'reviewed_by', 'closed_by', 'revision_no', 'row_count', 'error_count', 'security_version'])) {
                $casts[$field] = 'integer';
            }
        }
        return $casts;
    }
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        $relation = $relation ?: $this->guessBelongsToRelation();
        $result = parent::belongsTo($related, $foreignKey, $ownerKey, $relation);
        if (in_array(SoftDeletes::class, class_uses_recursive($result->getRelated()))) {
            $result->withTrashed();
        }
        return $result;
    }
}
