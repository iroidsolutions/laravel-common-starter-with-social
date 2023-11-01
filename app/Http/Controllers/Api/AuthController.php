<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegistrationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Passport\Client as OClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use function PHPUnit\Framework\isEmpty;

class AuthController extends Controller
{
    //
    public function Register(UserRegistrationRequest $request){
        $check=User::where('email',$request->email)->where('is_social',0)->first();
        if($check){
            return response()->json(['error' => 'Email allready exist'], 422);
        }
        $img = '';
        if($request->has('profile_pic')){
            $name = $request->file('profile_pic')->getClientOriginalName();
            $path = $request->file('profile_pic')->store('public/images');
            $img = $path;

        }
        $password = $request->password;
        $data = $request->all();
        $data['time_zone'] = is_null($data['time_zone'])?"UTC":$data['time_zone'];
        $data['remember_token'] = Str::random(10);
        $data['password'] = bcrypt($data['password']);
        $user = User::create($data);
        $id=$user->id;
        $user = User::where('id', $id)->update(['profile_pic' => $img]);
        $user = User::whereId($id)->first();
        $oClient = OClient::where('password_client', 1)->orderBy('id','desc')->first();
        $accessToken = $this->getTokenAndRefreshToken($oClient, $user->email, $password)->getData();
        return (new UserResource($user))->additional(['data' => ['access_token' => $accessToken]]);

    }


    public function Login(Request $request){

        $user = User::where('email',$request->email)->where('is_social',0)->first();
        if($user && (Hash::check($request->password,$user->password))){
            $user->last_login = Carbon::now();
            $user->save();
            $oClient = OClient::where('password_client', 1)->first();
            $accessToken = $this->getTokenAndRefreshToken($oClient, $user->email, $request->password)->getData();
            return (new UserResource($user))->additional(['data' => ['access_token' => $accessToken]]);


        }else{
            return response()->json(['error' => 'Sorry, wrong email address or password. Please try again.'], 422);
        }

    }
    
    public function getTokenAndRefreshToken(OClient $oClient, $email, $password) {
        $oClient = OClient::where('password_client', 1)->orderBy('id','desc')->first();
        $http = new Client;
        $url = env('APP_URL').'/oauth/token';
        try{
            $response = $http->request('POST', $url, [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => $oClient->id,
                    'client_secret' => $oClient->secret,
                    'username' => $email,
                    'password' => $password,
                    'scope' => '*',
                ],
            ]);
            $result = json_decode((string) $response->getBody(), true);
            return response()->json($result, 200);
        }catch (GuzzleException $exception) {
            // dd($exception);
            if ($exception->getCode() === 400) {
                throw new UnauthorizedHttpException('', 'Incorrect email or password');
            }
        }

    }

    

    
}