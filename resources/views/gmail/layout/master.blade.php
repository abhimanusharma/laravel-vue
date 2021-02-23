<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="{{asset('bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">

    <link href="//cdn.materialdesignicons.com/5.4.55/css/materialdesignicons.min.css" rel="stylesheet">

    <title>@yield('title')</title>
</head>
<body>

<div class="container-fluid mt-5">
    @yield('container')
</div>


<script defer src="{{asset('js/jquery-3.5.1.min.js')}}"></script>

<script defer src="{{asset('js/alpha.js')}}" ></script>

</body>
</html>
