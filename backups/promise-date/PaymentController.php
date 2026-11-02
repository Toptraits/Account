<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\FeePaymentItem;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class PaymentController extends Controller {
 public function store(Request $request){$data=$request->validate(['student_id'=>['required','integer','exists:students,id'],'amount'=>['required','numeric','gt:0','max:100000000'],'method'=>['required','in:Cash,UPI,Bank transfer,Cheque,Card'],'reference'=>['nullable','string','max:120'],'received_at'=>['required','date','before_or_equal:today'],'notes'=>['nullable','string','max:1000']]);
  $payment=DB::transaction(function()use($data,$request){$student=Student::whereKey($data['student_id'])->lockForUpdate()->firstOrFail();$outstanding=(float)$student->outstanding;if((float)$data['amount']>$outstanding+0.0001)throw ValidationException::withMessages(['amount'=>['Payment cannot exceed the student outstanding balance of '.number_format($outstanding,2,'.','').'.']]);$payment=FeePayment::create(['receipt_number'=>'PENDING','student_id'=>$student->id,'amount'=>$data['amount'],'method'=>$data['method'],'reference'=>$data['reference']??null,'received_at'=>$data['received_at'],'status'=>'verified','recorded_by'=>$request->user()->id,'notes'=>$data['notes']??null]);$payment->update(['receipt_number'=>'R-'.str_pad((string)$payment->id,6,'0',STR_PAD_LEFT)]);$remaining=(float)$data['amount'];$fees=$student->fees()->orderBy('due_date')->orderBy('id')->lockForUpdate()->get();foreach($fees as $fee){if($remaining<=0)break;$paid=(float)FeePaymentItem::where('student_fee_id',$fee->id)->whereHas('payment',fn($q)=>$q->where('status','verified'))->sum('amount');$feeBalance=max(0,(float)$fee->amount-$paid);$apply=min($remaining,$feeBalance);if($apply>0){FeePaymentItem::create(['fee_payment_id'=>$payment->id,'student_fee_id'=>$fee->id,'amount'=>number_format($apply,2,'.','')]);$remaining-=$apply;}}return $payment->load('student','items');});
  return response()->json(['message'=>'Payment recorded.','data'=>['id'=>$payment->id,'receipt_number'=>$payment->receipt_number,'student'=>['id'=>$payment->student->id,'name'=>$payment->student->name,'admission_number'=>$payment->student->admission_number],'amount'=>$payment->amount,'method'=>$payment->method,'reference'=>$payment->reference,'received_at'=>$payment->received_at->toDateString(),'status'=>$payment->status,'items'=>$payment->items,'receipt_url'=>'/api/payments/'.$payment->id.'/receipt']],201);
 }
 public function receipt(FeePayment $payment){$payment->load('student','items.studentFee.feeType','recorder');return response()->json(['data'=>['receipt_number'=>$payment->receipt_number,'school'=>'Toptraits School OS','student_name'=>$payment->student->name,'admission_number'=>$payment->student->admission_number,'class_name'=>$payment->student->class_name,'amount'=>$payment->amount,'method'=>$payment->method,'reference'=>$payment->reference,'received_at'=>$payment->received_at->toDateString(),'status'=>$payment->status,'recorded_by'=>$payment->recorder?->name,'allocations'=>$payment->items->map(fn($i)=>['fee_type'=>$i->studentFee?->feeType?->name,'description'=>$i->studentFee?->description,'amount'=>$i->amount])]]);}
 public function studentHistory(Student $student)
{
    $payments = FeePayment::where('student_id', $student->id)
        ->with('items.studentFee.feeType')
        ->orderByDesc('received_at')
        ->orderByDesc('id')
        ->get()
        ->map(function ($payment) {
            return [
                'id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'amount' => $payment->amount,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'received_at' => $payment->received_at
                    ? $payment->received_at->toDateString()
                    : null,
                'status' => $payment->status,
                'allocations' => $payment->items->map(function ($item) {
                    return [
                        'fee_type' => $item->studentFee?->feeType?->name
                            ?? 'Fee type unavailable',
                        'description' => $item->studentFee?->description,
                        'amount' => $item->amount,
                    ];
                })->values(),
            ];
        })->values();

    return response()->json([
        'data' => [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'admission_number' => $student->admission_number,
                'class_name' => $student->class_name,
            ],
            'outstanding' => $student->outstanding,
            'payments' => $payments,
            'total_payments' => $payments->count(),
        ],
    ]);
}
}
