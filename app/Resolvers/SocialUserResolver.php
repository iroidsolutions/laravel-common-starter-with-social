<?php
namespace App\Resolvers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Contracts\Auth\Authenticatable;
use Coderello\SocialGrant\Resolvers\SocialUserResolverInterface;

class SocialUserResolver implements SocialUserResolverInterface
{
    /**
     * Resolve user by provider credentials.
     *
     * @param string $provider
     * @param string $accessToken
     *
     * @return Authenticatable|null
     */
    public function resolveUserByProviderCredentials(string $provider, string $accessToken):? Authenticatable
    {
        // dd($provider);
        try {
            // dd($provider);
            $user = Socialite::driver($provider)->userFromToken($accessToken);
            $socialAccount = User::whereProviderId($user->id)
                            ->whereProvider($provider)
                            ->whereIsSocial(1)
                            ->first();

            // dd($socialAccount);
            if (isset($socialAccount)) {
                return $socialAccount;
            }

        } catch (\Throwable $th) {
            dd($th);
            return null;
        }

        return null;
    }
}