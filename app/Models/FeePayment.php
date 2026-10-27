<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FeePayment extends Model {
    protected $fillable=['receipt_number','student_id','amount','method','reference','received_at','status','recorded_by','notes'];
    protected function casts(): array {return ['amount'=>'decimal:2','received_at'=>'date'];}
    public function student(){return $this->belongsTo(Student::class);} public function items(){return $this->hasMany(FeePaymentItem::class);} public function recorder(){return $this->belongsTo(User::class,'recorded_by');}
}
