<?php
/**
 * @author QuanTM
 * @date: 8/8/2019 2:00 PM
 */

namespace App\Http\Controllers;


use App\Helpers\SidebarMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @menu
 *  [
 * 'name' => 'Thống kê',
 * 'icon' => 'fa fa-line-chart',
 * 'url' => '/xadmin/posts/index',
 * 'order' => 100,
 * 'children' => [
 * [
 * 'name' => 'Thêm bài viết',
 * 'icon' => 'fa fa-plus',
 * 'url' => '/xadmin/posts/create',
 * ]
 * ]
 * ]
 * Class AppController
 * @package App\Http\Controllers
 */
class AppController extends Controller
{
    protected $viewVars = [
        'title' => 'App'
    ];

    protected $name = '';
    protected $icon = '';


    public function __invoke($action, Request $request)
    {

        $ret = $this->$action($request);
        if ($ret === null) {
            throw new \Exception("$action result is empty");
        }

        return $ret;
    }
}
