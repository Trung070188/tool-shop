<?php

namespace App\Helpers;

class OdooHelper
{
    private static function fetch($model, $method = 'search_read', $fields = null, $filter = [], $limit = null)
    {
        require_once(public_path('vendor/ripcord/ripcord.php'));

        $url = config('odoo.url');
        $url_auth = $url . '/xmlrpc/2/common';
        $url_exec = $url . '/xmlrpc/2/object';

        $db = config('odoo.db');
        $username = config('odoo.username');
        $password = config('odoo.password');

        static $uid;

        if (!$uid) {
            $common = \ripcord::client($url_auth);
//            $common->version();

            $uid = $common->authenticate($db, $username, $password, array());
//            print("<p>Your current user id is '${uid}'</p>");
        }

        $models = \ripcord::client($url_exec);

        $data = $models->execute_kw($db, $uid, $password,
            $model, $method,
            array(
                $filter
            ),
            array(
                'fields' => $fields,
                'context' => array('lang' => 'vi_VN'),
                'limit' => $limit,
            )
        );

        return $data;
    }

    public static function getProducts()
    {
        $model = 'product.template';
        $method = 'search_read';
        $field = array(
            'id',
            'name',
            'default_code',
            'active',
            'categ_id',
            'display_name',
            'description',
            'product_tooltip',
            '__last_update',
            'standard_price',
            'list_price',
            'qty_available',
            'write_date',
        );

        $products = self::fetch($model, $method, $field);

        return $products;
    }

    public static function getCategories()
    {
        $model = 'product.category';
        $method = 'search_read';
        $field = array(
            'id', 'name', 'parent_id', '__last_update',
        );

        return self::fetch($model, $method, $field);
    }

    public static function getWarehouses()
    {
        $model = 'stock.warehouse';

        return self::fetch($model);
    }
}
