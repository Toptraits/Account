<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FeePaymentItem extends Model { protected $fillable=['fee_payment_id','student_fee_id','amount']; protected function casts(): array{return ['amount'=>'decimal:2'];} public function payment(){return $this->belongsTo(FeePayment::class,'fee_payment_id');} public function studentFee(){return $this->belongsTo(StudentFee::class);} }
