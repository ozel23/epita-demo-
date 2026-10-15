<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SyncReceipt extends Model {protected $guarded=['id'];protected $casts=['response'=>'array'];}
