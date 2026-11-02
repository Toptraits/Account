<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\Student;
use Illuminate\Http\Request;
class CopilotController extends Controller { public function query(Request $request){$data=$request->validate(['question'=>['required','string','max:500']]);$q=mb_strtolower($data['question']);if(preg_match('/today.*collection|collection.*today|collected today/u',$q)){$amount=FeePayment::whereDate('received_at',today())->where('status','verified')->sum('amount');$answer="Today's recorded collection is ₹".number_format((float)$amount,2,'.',',').'.';}else{$student=Student::query()->where(function($query)use($data){$query->where('name','ilike','%'.$data['question'].'%')->orWhere('admission_number','ilike','%'.$data['question'].'%');})->first();if($student){$answer=$student->name.' ('.$student->admission_number.') has ₹'.number_format((float)$student->outstanding,2,'.',',').' outstanding.';}elseif(preg_match('/overdue|outstanding|balance|due amount|fees/u',$q)){$answer='The API currently supports direct student lookups and today’s collection. Configure an authorized AI provider to answer broader questions.';}else{$answer='Copilot API is connected, but a language model has not been configured yet.';}}return response()->json(['data'=>['question'=>$data['question'],'answer'=>$answer,'mode'=>'deterministic_scaffold']]);} }
