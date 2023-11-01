<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    //

    public function logout(Request $request)
    {
        // $devices=deviceTokens::where('device_id',$request->device_id)->where('user_id',Auth::user()->id)->delete();
        $accessToken = Auth::user()->token()->delete();

        return ['message' => 'Logged out successfully'];
    }
}
