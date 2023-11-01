<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Passport\Client as OClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpFoundation\Response as ResponseWithConstant;
use Laravel\Socialite\Two\User as OAuthTwoUser;
use Illuminate\Support\Str;

class SocialLoginController extends Controller
{
    //
    public function redirectToProvider(string $provider) : RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }
    public function handleProviderCallback(string $provider){

        $user =  Socialite::driver($provider)->stateless()->user();
        dd($user);

    }


    public function socialLogin(Request $request){
        $validated = $request->validate([
            'email' => 'required',
            'provider_id' => 'required',
            'provider_type' => 'required',
            'token' => 'required',
        ]);
        
        $user=User::where('provider',$request->provider_type)->where('provider_id',$request->provider_id)->first();
        if($user){
            $accessToken = $this->getTokenAndRefreshTokenForSocial($request->provider_type, $request->token)->getData();
            return (new UserResource($user))->additional(['data' => ['access_token' => $accessToken]]);
        }else{
            $user=new User();
            $user->email=$request->email;
            $user->provider_id=$request->provider_id;
            $user->provider=$request->provider_type;
            $user->profile_pic=$request->profile_picture;
            $user->first_name=$request->full_name;
            $user->last_name='';
            $user->is_social=1;
            $user->created_at=Carbon::now();
            $user->updated_at=Carbon::now();
            $user->save();
            
            $accessToken = $this->getTokenAndRefreshTokenForSocial($request->provider_type, $request->token)->getData();
            
            return (new UserResource($user))->additional(['data' => ['access_token' => $accessToken]]);
        }

    }

    public function allSocialLogin(Request $request){
        $validated = $request->validate([
            'provider_type' => 'required',
            'token' => 'required',
        ]);
        $provider = $request->provider_type;
        $token = $request->token;
        try{
            $socialUser = Socialite::driver($provider)->userFromToken($token);
        }catch(Exception $e){
            return response()->json(['data' => ['message'=> $e->getMessage()]],$e->getCode());
        }
        $check=User::where('email', $socialUser->email)->where('is_social',1)->where('provider', $provider)->first();
        if($check){
            $accessToken = $this->getTokenAndRefreshTokenForSocial($provider, $request->token)->getData();
            $check->access_token = $accessToken;
            return response()->json(['data' => $check]);
        }
        $user = new User();
        $user->email = $socialUser->email;
        $user->provider_id = $socialUser->id;
        $user->provider = $provider;
        $user->profile_pic = $socialUser->avatar_original;
        $user->first_name = $socialUser->name;
        $user->last_name = '';
        $user->is_social = 1;
        $user->created_at = Carbon::now();
        $user->updated_at = Carbon::now();
        $user->save();

        $accessToken = $this->getTokenAndRefreshTokenForSocial($provider, $request->token)->getData();
        $user->access_token=$accessToken;
        return response()->json(['data'=> $user]);
    }



    public function getTokenAndRefreshTokenForSocial($provider, $providertoken) {


        $oClient = OClient::where('password_client', 1)->orderBy('id','desc')->first();
        // dd($providertoken);
        $http = new Client;
        $url = env('APP_URL').'/oauth/token';
        try{
            $response = $http->request('POST', $url, [
                'form_params' => [
                    'grant_type' => 'social',
                    'client_id' => $oClient->id,
                    'client_secret' => $oClient->secret,
                    'provider' => $provider,
                    'access_token' => $providertoken,
                ],
            ]);
            $result = json_decode((string) $response->getBody(), true);
            return response()->json($result, 200);
        }catch (GuzzleException $exception) {
            if ($exception->getCode() === 400) {
                throw new UnauthorizedHttpException('', 'Incorrect email or password');
            }
        }

    }

    /**
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function appleLogin(Request $request)
    {
        $provider = 'apple';
        $token = $request->token;
        $socialUser = Socialite::driver($provider)->userFromToken($token);
        $user = $this->getLocalUser($socialUser);

        $client = DB::table('oauth_clients')
            ->where('password_client', true)
            ->first();

        if (!$client) {
            return response()->json([
                'message' => trans('validation.passport.client_error'),
                'status' => ResponseWithConstant::HTTP_INTERNAL_SERVER_ERROR
            ], ResponseWithConstant::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = [
            'grant_type' => 'social',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'provider' => 'apple',
            'access_token' => $token
        ];
        $request = Request::create('/oauth/token', 'POST', $data);

        $content = json_decode(app()->handle($request)->getContent());
        if (isset($content->error) && $content->error === 'invalid_request') {
            return response()->json(['error' => true, 'message' => $content->message]);
        }

        return response()->json(
            [
                'error' => false,
                'data' => [
                    'user' => $user,
                    'meta' => [
                        'token' => $content->access_token,
                        'expired_at' => $content->expires_in,
                        'refresh_token' => $content->refresh_token,
                        'type' => 'Bearer'
                    ],
                ]
            ],
            ResponseWithConstant::HTTP_OK
        );
    }


    protected function getLocalUser(OAuthTwoUser $socialUser): ?User
    {
        $user = User::where('email', $socialUser->email)->first();

        if (!$user) {
            $user = $this->registerAppleUser($socialUser);
        }

        return $user;
    }

    protected function registerAppleUser(OAuthTwoUser $socialUser): ?User
    {
       $user = User::create(
            [
                'full_name' => request()->fullName ? request()->fullName : 'Apple User',
                'email' => $socialUser->email,
                'password' => Str::random(30), // Social users are password-less
                
            ]
        );
        return $user;
    }
}