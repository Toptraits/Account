<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FeeReminder extends Model { protected $fillable=['student_id','user_id','reminder_type','channel','status','scheduled_at','sent_at','message']; protected function casts(): array{return ['scheduled_at'=>'datetime','sent_at'=>'datetime'];} }
