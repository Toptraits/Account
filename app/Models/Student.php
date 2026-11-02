<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Student extends Model {
    use SoftDeletes;
    protected $fillable = [
        'admission_number',
        'name',
        'class_name',
        'parent_name',
        'parent_mobile',
        'is_active',
        'section',
        'mother_name',
        'date_of_birth',
        'religion',
        'caste',
        'aadhaar_number',
    ];
    public function fees(){ return $this->hasMany(StudentFee::class); }
    public function payments(){ return $this->hasMany(FeePayment::class); }
    public function getTotalFeesAttribute(): string { return (string) $this->fees()->sum('amount'); }
    public function getTotalPaidAttribute(): string { return (string) $this->payments()->where('status','verified')->sum('amount'); }
    public function getOutstandingAttribute(): string { return (string) max(0, (float)$this->total_fees - (float)$this->total_paid); }
}
