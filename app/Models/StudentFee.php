<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StudentFee extends Model {
    protected $fillable=['student_id','fee_type_id','description','amount','due_date','academic_year'];
    protected function casts(): array {return ['amount'=>'decimal:2','due_date'=>'date'];}
    public function student(){return $this->belongsTo(Student::class);} public function feeType(){return $this->belongsTo(FeeType::class);}
}
