@extends('gmail.layout.master')

@section('container')
    <div class="row">
        <div class="col-4 offset-4">
            @if(LaravelGmail::check())
                <a class="btn btn-danger" href="{{ route('gmail.logout') }}">logout</a>
            @else
                <a class="btn btn-primary" href="{{ route('gmail.login') }}">login gmail</a>
            @endif

            <a class="ml-4 btn btn-danger" href="{{ route('xero.connect') }}">login xero</a>

        </div>
    </div>
@endsection
