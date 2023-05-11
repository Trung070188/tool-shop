<?php
$defaultTitle = config('page.defaultTitle');
$pageTitle = isset($title) ? $title . ' | ' . $defaultTitle : $defaultTitle;
$title = $title ?? '';
/*if (config('app.env') === 'production') {
    $pageTitle = '[PROD]' . $pageTitle;
}*/
$serverTime = date('Y-m-d H:i:s');
?>
<html lang="vi"
      style="--primary01:rgba(56, 202, 179, 0.1); --primary02:rgba(56, 202, 179, 0.2); --primary03:rgba(56, 202, 179, 0.3); --primary06:rgba(56, 202, 179, 0.6); --primary09:rgba(56, 202, 179, 0.9); --primary05:rgba(56, 202, 179, 0.5);">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="Description" content="ASOAPP">
    <meta name="Author" content="ASOAPP">
    <meta name="Keywords"
          content="">
    <!-- Title --> <title>{{$pageTitle}}</title> <!-- Favicon -->
    <link rel="icon" href="/assets/img/brand/vnpost_logo.svg" type="image/x-icon"> <!-- Icons css -->
    <link href="/assets/css/icons.css" rel="stylesheet"> <!--  bootstrap css-->
    <link id="style" href="/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"> <!-- style css -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/style-dark.css" rel="stylesheet">
    <link href="/assets/css/style-transparent.css" rel="stylesheet"> <!---Skinmodes css-->
    <link href="/assets/css/skin-modes.css" rel="stylesheet"> <!-- INTERNAL Switcher css -->

    <link href="/vendor/daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="/vendor/nprogress/nprogress.css" rel="stylesheet">
    <link href="/vendor/toastr/toastr.min.css" rel="stylesheet">

    <?php
        echo asset_css('assets/css/app.css')
    ?>
    <script>
        window.APP_NAME = 'MAIN';
        window.$SERVER_TIME = '{{$serverTime}}';
        window.$json = JSON.parse('{!! addslashes(json_encode($jsonData?? new stdClass())) !!}');
        window.$componentName = '{{$component}}';
        window.$sideBarMenus = JSON.parse('{!! addslashes(json_encode(config('menu'))) !!}');
        window.$csrf = '{{csrf_token()}}';
        window.$pageTitle = '{{$title}}';
        window.$scripts = JSON.parse('{!! addslashes(json_encode(@$scripts)) !!}');
        <?php
        $user = auth_user();
        $auth = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'avatar' => get_gravatar($user->id)
        ];
        ?>
            window.$auth =  JSON.parse('{!! addslashes(json_encode($auth)) !!}');
    </script>

</head>
<body class="ltr main-body app sidebar-mini sidebar-gone">
<div id="global-loader" ><img src="/assets/img/loader.svg" class="loader-img" alt="Loader">
</div> <!-- /Loader --> <!-- Page -->

<div id="root-app"></div>

<a href="#top" id="back-to-top"><i
        class="las la-arrow-up"></i></a> <!-- JQuery min js -->

<div class="main-navbar-backdrop"></div>

<script
    src="https://code.jquery.com/jquery-3.6.1.min.js"
    integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ="
    crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="/vendor/daterangepicker/moment.min.js"></script>
<script src="/vendor/daterangepicker/daterangepicker.js"></script>
<?php
echo asset_js('assets/js/app.js')
?>
</body>
</html>
