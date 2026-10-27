<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\FeeType;
class FeeTypeController extends Controller { public function index(){return response()->json(['data'=>FeeType::where('is_active',true)->orderBy('name')->get(['id','name','description'])]);} }
