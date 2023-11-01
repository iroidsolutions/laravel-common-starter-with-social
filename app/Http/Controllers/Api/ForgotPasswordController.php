<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    //
    

    public function forgot(Request $request) {
        $credentials = $request->validate(['email' => 'required|email|exists:users']);
        
        $response = Password::sendResetLink($request->only('email'));
        if($response=='passwords.throttled'){
            return response()->json(["msg" => 'Reset password link allready sent on your email id.'],421);
        }else if($response=='passwords.user'){
            return response()->json(['error' => 'Sorry, wrong email address. Please try again.'], 422);
        }else{
            return response()->json(["msg" => 'Reset password link sent on your email id.']);
        }
        
    }
    public function updatePassword(Request $request){

            dd($request->all());
    }


}
