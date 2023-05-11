<?php echo "@extends('admin.layouts.main')\n";?>
<?php echo "@section('content')\n";?>

    <div class="page-header card">
        <div class="row align-items-end">
            <div class="col-lg-8">
                <div class="page-header-title">

                    <div class="d-inline">
                        <h5>{{$name . 'Edit'}}</h5>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="page-header-breadcrumb">
                    <ul class=" breadcrumb breadcrumb-title">
                        <li class="breadcrumb-item">
                            <a href="<?php echo '{{ route(\'home\') }}'?>"><i class="feather icon-home"></i></a>
                        </li>
                        <li class="breadcrumb-item"><a href="#!">{{$name . 'Edit'}}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div  id="{{$table.'-form'}}">

    </div>

<?php echo "@endsection" ?>

