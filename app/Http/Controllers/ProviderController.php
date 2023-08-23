<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    //

    public function redirect($provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback($provider)
    {
        try {

            $socialUser = Socialite::driver($provider)->stateless()->user();

            if(User::where('email', $socialUser->getEmail())->exists()){
                return redirect('/login')->withErrors(['email' => 'You had used a different method to login']);
            }

            $user = User::where([
                'provider' => $provider,
                'provider_id' => $socialUser->id
            ])->first();

            if(!$user){
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'username' => User::generateUsername($socialUser->getNickname()),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'provider_token' => $socialUser->token
                ]);

                $user->sendEmailVerificationNotification();
            }

            Auth::login($user);

            return redirect('/dashboard');

        } catch (\Exception $e) {

            redirect('/');
        }

    }
}
