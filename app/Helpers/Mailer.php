<?php

namespace App\Helpers;

class Mailer
{
    public static function send($mail, $content, $type, $_params = null)
    {
        $SSOHelper = new SSOHelper();
        $params = [
            'mail' => $mail,
            'content' => $type === 'raw' ? $content : json_encode($content),
            'type' => $type,
        ];

        if ($_params) $params = array_merge($params, $_params);

        return $SSOHelper->requestPost('/api/sendmail', [
            'form_params' => $params
        ], true);
    }
}
