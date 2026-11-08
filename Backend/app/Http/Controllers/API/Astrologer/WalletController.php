<?php

namespace App\Http\Controllers\API\Astrologer;

use App\Http\Controllers\Controller;
use App\Models\AstrologerModel\WithDrawRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function sendWithdrawRequest(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }
            $data = $req->only(
                'astrologerId',
                'withdrawAmount'
            );
            $validator = Validator::make($data, [
                'astrologerId' => 'required',
                'withdrawAmount' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }
            WithDrawRequest::create([
                'astrologerId' => $req->astrologerId,
                'withdrawAmount' => $req->withdrawAmount,
                'status' => 'Pending',
                'createdBy' => $id,
                'modifiedBy' => $id,
            ]);
            return response()->json([
                'message' => 'Request send to admin successfully',
                'status' => 200,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => 'Fail to send request',
                'status' => 500,
            ], 500);
        }
    }

    public function updateWithdrawRequest(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }
            $data = $req->only(
                'id',
                'astrologerId',
                'withdrawAmount'
            );
            $validator = Validator::make($data, [
                'id' => 'required',
                'astrologerId' => 'required',
                'withdrawAmount' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }
            $withdrawRequest = WithDrawRequest::find($req->id);
            if ($withdrawRequest) {
                $withdrawRequest->astrologerId = $req->astrologerId;
                $withdrawRequest->withdrawAmount = $req->withdrawAmount;
                $withdrawRequest->modifiedBy = $id;
                $withdrawRequest->updated_at = Carbon::now()->timestamp;
                $withdrawRequest->update();
            }
            return response()->json([
                'message' => 'Request update & send to admin successfully',
                'status' => 200,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getWithdrawRequest(Request $req)
    {
        try {
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            $withdrawRequest = DB::table('withdrawrequest')
                ->join('astrologers', 'astrologers.id', '=', 'withdrawrequest.astrologerId')
                ->select('withdrawrequest.*');

            DB::table('withdrawrequest')
                ->join('astrologers', 'astrologers.id', '=', 'withdrawrequest.astrologerId')
                ->count();
            return response()->json([
                'message' => 'Request send to admin successfully',
                'status' => 200,
                'recordList' => $withdrawRequest->get(),
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}
