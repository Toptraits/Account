<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\PaymentCommitment;
use Illuminate\Http\Request;
class CommitmentController extends Controller { public function store(Request $request){$data=$request->validate(['student_id'=>['required','integer','exists:students,id'],'outcome'=>['required','in:promise,time,no_answer,fee_query'],'promised_amount'=>['nullable','numeric','min:0','max:100000000'],'promised_date'=>['nullable','date','required_if:outcome,promise'],'note'=>['nullable','string','max:1000']]);$data['user_id']=$request->user()->id;$data['status']=$data['outcome']==='promise'?'open':'closed';$row=PaymentCommitment::create($data);return response()->json(['message'=>'Call outcome saved.','data'=>$row],201);} }
