@extends('gmail.layout.master')

@section('container')

    <div class="row justify-content-end">
        <div class="col-1">
        @if(LaravelGmail::check())
            <a class="btn btn-danger" href="{{ route('gmail.logout') }}">logout</a>
        @else
            <a class="btn btn-primary" href="{{ route('gmail.login') }}">login</a>
        @endif
        </div>
    </div>

    @include('gmail.search-transaction')


    <div class="row">
        <div class="col-3">
            <div class="card">
                <div class="card-body">
                    @include('gmail.navigation')
                </div>
            </div>

        </div>
        <div class="col-9">
            @if(session('success'))
                <div class="alert alert-success">{{session('success')}}</div>
            @endif

            <div class="card">
                <div class="card-body">

                    @if(!empty($data))
                        @foreach($data['messages'] as $message)

                        @if($data['current_folder']==='DRAFT')

                        <a style="text-decoration: none; color:#555;" href="{{route('gmail.draft',['id'=>$message['id']])}}">

                        @else

                            <a style="text-decoration: none; color:#555;" href="{{route('singleEmail',
                        ['type'=>$data['current_folder'],'id'=>$message['id']])
                        }}">

                            @endif

                            <div class="row pt-2 pb-2 mb-1 border-bottom border-1 border-secondary">
                                <div class="col-2 dt {{$message['is_unread']?'fw-bold':''}}">{{$message['from']}}</div>
                                <div class="col-7"><span class="{{$message['is_unread']?'fw-bold':''}}">{{$message['subject']}}</span> {{$message['excerpt']}}</div>
                                <div class="col-1">@if($message['has_attachments'])<span class="mdi mdi-attachment"></span>@endif</div>
                                <div class="col-2 {{$message['is_unread']?'fw-bold':''}}">{{$message['date']}}</div>
                            </div>
                        </a>
                        @endforeach
                    @else
                        <div class="alert alert-warning" role="alert">
                            No Emails Found!!!
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
