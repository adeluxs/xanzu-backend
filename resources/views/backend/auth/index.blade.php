<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<!--Head-->
@section('style')
    <style>
        .admin-auth .login .side-img .title {
            background: rgb(98 52 255);
        }
    </style>
@endsection
@include('backend.include.__head')
<!--/Head-->

<body>

    <!--Auth Page-->
    <div class="admin-auth">
        <!--Notification-->
        @include('global._notify')

        @yield('auth-content')
    </div>
    <!--/Auth Page-->

    <!--Script-->
    @include('backend.include.__script')
    <!--/Script-->

</body>

</html>
