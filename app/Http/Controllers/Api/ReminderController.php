<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\FeeReminder;
use Illuminate\Http\Request;
class ReminderController extends Controller { public function store(Request $request){$data=$request->validate(['student_id'=>['required','integer','exists:students,id'],'reminder_type'=>['required','in:overdue,due_soon,commitment_follow_up'],'channel'=>['required','in:sms,whatsapp'],'message'=>['nullable','string','max:2000'],'scheduled_at'=>['nullable','date']]);$data['user_id']=$request->user()->id;$data['status']='queued';$row=FeeReminder::create($data);return response()->json(['message'=>'Reminder queued in the database. No message has been sent yet.','data'=>$row],201);} }
