<?php
    $defaultTitle = config('page.defaultTitle');
    $pageTitle = isset($title) ? $title . ' - ' . $defaultTitle : $defaultTitle;
    $title = isset($title) ? $title : '';
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">


    <title>{{$pageTitle}}</title>
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Page Vendors Styles(used by this page)-->
    <link href="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Page Vendors Styles-->
    <!--begin::Global Theme Styles(used by all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/custom/prismjs/prismjs.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Theme Styles-->
    <!--begin::Layout Themes(used by all pages)-->
    <link href="{{ asset('assets/css/themes/layout/header/base/light.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/themes/layout/header/menu/dark.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/themes/layout/brand/dark.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/themes/layout/aside/dark.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('/assets/css/viewer.min.css') }}" rel="stylesheet" type="text/css" />
    <?php
       echo asset_css('assets/css/app.css')
    ?>
    <!--end::Layout Themes-->
    <link rel="shortcut icon" href="{{ asset(config('page.favicon')) }}" />
    <script>
        window.APP_NAME = 'MAIN';
        window.$json = JSON.parse('{!! addslashes(json_encode($jsonData?? new stdClass())) !!}');
        window.$componentName = '{{$component}}';
        window.$sideBarMenus = JSON.parse('{!! addslashes(json_encode(config('menu'))) !!}');
        window.$csrf = '{{csrf_token()}}';
        window.$pageTitle = '{{$title}}';
        window.$scripts = JSON.parse('{!! addslashes(json_encode(@$scripts)) !!}');
        window.$LAST_REPORT_AT = '{{\App\Models\ConfigModel::readConfig('last_report_at')}}';
        <?php
            $user = auth_user();
            $auth = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name
            ];
        ?>
        window.$auth =  JSON.parse('{!! addslashes(json_encode($auth)) !!}');
    </script>
</head>
<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable">
<div id="root-app"></div>

<!-- Optional JavaScript; choose one of the two! -->

<!-- Option 1: jQuery and Bootstrap Bundle (includes Popper) -->

<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js" integrity="sha256-eTyxS0rkjpLEo16uXTS0uVCS4815lc40K2iVpWDvdSY=" crossorigin="anonymous"></script>
<script src="{{asset('/assets/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('/vendor/ckeditor/ckeditor.js')}}"></script>
<script src="/vendor/daterangepicker/moment.min.js"></script>
<script src="/vendor/daterangepicker/daterangepicker.js"></script>
<?php
    echo asset_js([
        'assets/js/app.js'
    ])
?>


</body>
</html>
