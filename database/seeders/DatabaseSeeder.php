<?php
namespace Database\Seeders;
use App\Models\FeeType;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder {
 public function run(): void {
  $user=User::updateOrCreate(['mobile'=>'9448102785'],['name'=>'Demo Accountant','email'=>'accountant@example.test','password'=>Hash::make(bin2hex(random_bytes(24))),'role'=>'accountant','is_active'=>true]);
  $types=[];foreach([['Tuition','Tuition fee'],['Transport','School transport fee'],['Books','Books and learning materials']] as [$name,$desc]){$types[$name]=FeeType::firstOrCreate(['name'=>$name],['description'=>$desc,'is_active'=>true]);}
  $records=[['A0822','Nisha Verma','8-A','Rita Verma','9876500001',20500],['A0412','Aarav Rao','8-B','Mohan Rao','9876500002',18500],['A0733','Rohan Das','7-A','Anita Das','9876500003',17250],['A0745','Zoya Ansari','7-A','Farah Ansari','9876500004',16500],['A0599','Meera Nair','6-A','Suresh Nair','9876500005',15500],['A0640','Tara Pillai','6-B','Kavya Pillai','9876500006',14000],['A0413','Diya Rao','6-A','Mohan Rao','9876500002',12000],['A0877','Ishaan Gupta','9-B','Neha Gupta','9876500007',11000]];
  foreach($records as [$admission,$name,$class,$parent,$mobile,$amount]){$student=Student::withTrashed()->firstOrNew(['admission_number'=>$admission]);$student->fill(['name'=>$name,'class_name'=>$class,'parent_name'=>$parent,'parent_mobile'=>$mobile,'is_active'=>true]);$student->deleted_at=null;$student->save();if(!$student->fees()->exists()){StudentFee::create(['student_id'=>$student->id,'fee_type_id'=>$types['Tuition']->id,'description'=>'Outstanding tuition fee','amount'=>$amount,'due_date'=>now()->subDays(7)->toDateString(),'academic_year'=>'2026-2027']);}}
 }
}
