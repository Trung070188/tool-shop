<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\AppController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends AppController
{
    public function index()
    {
        $title = 'Thống kê';
        $component = 'DashboardIndex';

        return vue(compact('component', 'title'));
    }
}
