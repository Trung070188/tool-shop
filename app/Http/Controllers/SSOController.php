<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SSOController
{
    public function index(Request $req)
    {
        $token = $req->get('_');
        $to = $req->get('to');

        $admin = $jwt = \Firebase\JWT\JWT::decode($token, config('jwt.secret'), array('HS256'));
        $userId = $admin->user->id;
        $user = User::find($userId);
        auth()->login($user);

        return redirect('/xadmin/' . $to);
    }
}
