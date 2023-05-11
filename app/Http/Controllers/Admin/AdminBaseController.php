<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

class AdminBaseController
{
    public function __invoke(Request $req)
    {
        // TODO: Implement __invoke() method.
        $action = $req->route('action', 'index');

        return $this->{$action}($req);
    }
}
