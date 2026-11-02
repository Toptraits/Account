<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PaymentCommitment extends Model { protected $fillable=['student_id','user_id','outcome','promised_amount','promised_date','note','status']; protected function casts(): array{return ['promised_amount'=>'decimal:2','promised_date'=>'date'];} public function student(){return $this->belongsTo(Student::class);} }
