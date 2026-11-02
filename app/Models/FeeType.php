<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FeeType extends Model { protected $fillable=['name','description','is_active']; public function fees(){return $this->hasMany(StudentFee::class);} }
