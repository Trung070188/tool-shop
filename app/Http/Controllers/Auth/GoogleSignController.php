<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleSignController
{
    public function login(Request $req)
    {
        $token = $req->token;

        try {
            $aud = googleClientId();
            $userInfo = curl_get_json('https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token);

            if (isset($userInfo['email']) && $userInfo['aud'] === $aud) {
                /**
                 * @var User $user
                 */
                $user = User::where('email', $userInfo['email'])->first();

                if (!$user) {
                    return [
                        'code' => 2,
                        'message' => 'Đăng nhập thất bại',
                    ];
                }

                $user->last_login = date('Y-m-d H:i:s');

                if (!empty($userInfo['picture']) && $userInfo['picture'] != $user->avatar) {
                    $user->avatar = $userInfo['picture'];
                }

                $user->save();
                Auth::login($user);

                return [
                    'code' => 200,
                    'redirect' => route('home'),
                ];
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return [
            'code' => 1,
            'message' => 'Đăng nhập thất bại',
        ];
    }
}
