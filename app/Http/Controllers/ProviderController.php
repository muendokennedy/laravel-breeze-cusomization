<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    //

    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        $socialUser = Socialite::driver($provider)->user();

        try {

            $socialUser = Socialite::driver($provider)->user();

            if(User::where('email', $socialUser->getEmail())->exists()){
                $formerprovider = User::where('provider', $provider);
                return redirect('/login')->withErrors(['email' => 'You had used a different method to login, specifically'. $formerprovider]);
            }

            $user = User::where([
                'provider' => $provider,
                'provider_id' => $socialUser->id,
            ])->first();

            if(!$user){
                $password = Str::random(12);
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'username' => User::generateUsername($socialUser->getNickname()),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'provider_token' => $socialUser->token,
                    'password' => $password
                ]);

                $user->sendEmailVerificationNotification();

                $user->update([
                    'password' => Hash::make($password)
                ]);
            }

            Auth::login($user);

            return redirect('/dashboard');

        } catch (\Exception $e) {

            return redirect('/login');
        }

    }
}
