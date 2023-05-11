<?php
$googleSignEnabled = config('services.google.enabled');
?>
    <!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Đăng nhập</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css"
          integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
    <link href="/assets/css/style.css" rel="stylesheet">

    @if ($googleSignEnabled)
        <meta name="google-signin-client_id"
              content="{{googleClientId()}}">
    @endif

    <style>
        #loginForm .form-control {
            width: 100%;
        }
        .bg-main-login {
            width: 100%;
            min-height: 100%;
            background: url("/assets/img/brand/bg_main.png") no-repeat 50% bottom, url("/assets/img/brand/bg.jpg") bottom;
            background-size: 100%;
            height: 100%;
            position: relative;
        }
    </style>
</head>
<body class="ltr error-page1 bg-main-login">
<div class="square-box">
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
</div>
<div class="page">
    <div class="page-single">
        <div class="container">
            <div class="row">
                <div
                    class="col-xl-5 col-lg-6 col-md-8 col-sm-8 col-xs-10 card-sigin-main py-4 justify-content-center mx-auto">
                    <div class="card-sigin ">
                        <div class="main-card-signin d-md-flex">
                            <div class="wd-100p">
                                <div class="d-flex mb-4"><a href="{{ route('home') }}" style="margin: auto">
                                        <img src="/images/logo.png" class="sign-favicon"
                                             style="width: 100%"
                                             alt="logo"></a>
                                </div>
                                <div class="">
                                    <div class="main-signup-header">
                                        <form method="post" id="loginForm" method="post" action="{{route('login')}}">
                                            {{csrf_field()}}
                                            <div class="form-group"><label>Tài khoản truy cập</label>
                                                <input class="form-control" placeholder="Tài khoản truy cập" name="email"
                                                    type="text" value="{{ old('email') }}" required autofocus></div>
                                            <div class="form-group"><label>Mật khẩu</label>
                                                <input class="form-control" placeholder="Mật khẩu" type="password"
                                                       required name="password">
                                            </div>

                                            @error('email')
                                            <div class="alert alert-danger" role="alert">
                                                <strong>Tên đăng nhập hoặc mật khẩu không hợp lệ</strong>
                                            </div>
                                            @enderror
                                            @error('password')
                                            <div class="alert alert-danger" role="alert">
                                                <strong>Tên đăng nhập hoặc mật khẩu không hợp lệ</strong>
                                            </div>
                                            @enderror
                                            <button class="btn btn-primary btn-block">Đăng nhập</button>

                                        </form>
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if ($googleSignEnabled)
    <script>
        var continueUrl = '{{@$_GET['c']}}';
        var csrfToken = '{{csrf_token()}}';

        function onGoogleLoaded() {
            console.log('onGoogleLoaded')
            gapi.load('auth2', function () {
                gapi.auth2.init();
            });
        }

        function onSignIn(googleUser) {
            var profile = googleUser.getBasicProfile();
            var params = {
                email: profile.getEmail(),
                id: profile.getId(),
                imageUrl: profile.getImageUrl(),
                token: gapi.auth2.getAuthInstance().currentUser.get().getAuthResponse().id_token
            };

            fetch('/auth/google-sign', {
                method: 'POST', // or 'PUT'
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(params),
            }).then((response) => response.json())
                .then((data) => {
                    if (data.code === 200) {
                        location.replace(continueUrl ? continueUrl : data.redirect);
                    } else {
                        alert(data.message);
                    }
                })
        }
    </script>

    <script src="https://apis.google.com/js/platform.js?onload=onGoogleLoaded" async defer></script>
@endif
</body>
</html>
