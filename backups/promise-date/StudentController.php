<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
class StudentController extends Controller {
 private function shape(Student $s): array { return ['id'=>$s->id,'admission_number'=>$s->admission_number,'name'=>$s->name,'class_name'=>$s->class_name,'parent_name'=>$s->parent_name,'parent_mobile'=>$s->parent_mobile,'total_fees'=>$s->total_fees,'total_paid'=>$s->total_paid,'outstanding'=>$s->outstanding,'fees'=>$s->fees()->with('feeType')->get()->map(fn($f)=>['id'=>$f->id,'fee_type'=>$f->feeType?->name,'description'=>$f->description,'amount'=>$f->amount,'due_date'=>$f->due_date?->toDateString()])]; }
 public function index(Request $request){$data=$request->validate(['search'=>['nullable','string','max:100'],'class_name'=>['nullable','string','max:40'],'per_page'=>['nullable','integer','min:1','max:100']]);$q=Student::query()->where('is_active',true);if(!empty($data['search'])){$term=$data['search'];$q->where(fn($x)=>$x->where('name','ilike','%'.$term.'%')->orWhere('admission_number','ilike','%'.$term.'%')->orWhere('class_name','ilike','%'.$term.'%'));}if(!empty($data['class_name']))$q->where('class_name',$data['class_name']);$rows=$q->orderBy('name')->paginate($data['per_page']??25);$rows->getCollection()->transform(fn($s)=>$this->shape($s));return response()->json($rows);}
 public function show(Student $student){return response()->json(['data'=>$this->shape($student)]);}
 public function store(Request $request){$data=$request->validate(['admission_number'=>['required','string','max:40','unique:students,admission_number'],'name'=>['required','string','max:150'],'class_name'=>['required','string','max:40'],'parent_name'=>['nullable','string','max:150'],'parent_mobile'=>['nullable','regex:/^[6-9][0-9]{9}$/']]);$student=Student::create($data+['is_active'=>true]);return response()->json(['message'=>'Student created.','data'=>$this->shape($student)],201);}
}
