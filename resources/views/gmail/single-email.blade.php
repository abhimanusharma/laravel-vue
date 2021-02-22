@extends('gmail.layout.master')

@section('container')

    <div class="row mb-3">
        <div class="col-2 offset-1">
        @include('gmail.common.dashboard-back')
        </div>

        <div class="col-4">
            @include('gmail.common.folder-drop-down')
        </div>

        <div class="col-5">
            <a class="btn btn-success" href="{{route('replyEmail', ['id'=> $data['message_id']])}}" ><span class="mdi
             mdi-reply"></span> Reply</a>
            &nbsp;
            <a class="btn btn-primary" href="{{route('forwardEmail', ['id'=>$data['message_id']])}}"><span class="mdi
             mdi-forward"></span> Forward</a>
            &nbsp;

            <a class="btn btn-danger" href="{{route('gmail.emailDelete', ['id'=>$data['message_id']])}}"><span
                    class="mdi
            mdi-delete"></span> Delete</a>

            @if($data['current_folder']==='TRASH')
            <a class="btn btn-success" href="{{route('gmail.emailRestore', ['id'=>$data['message_id']])}}"><span
            class="mdi mdi-delete-restore"></span> Restore</a>
            @endif


        </div>
    </div>


    <br />


        @foreach($data['messages'] as $message)
            @include('gmail.common.message-body')
        @endforeach

@endsection
