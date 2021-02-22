@extends('gmail.layout.master')

@section('container')



    <div class="row">

        <div class="col-8 offset-2">
            <div style="display: none;" class="alert alert-danger messagebox"></div>

            @include('gmail.common.dashboard-back')

            <div class="card">
                <div class="card-header bg-secondary text-white">
                    New Message
                </div>
                <div class="card-body">

                    @include('gmail.common.email-form')

                </div>
            </div>
        </div>
    </div>

    @include('gmail.common.email-editor-scripts', ['emailAreaId'=>'gmailEmailArea'])

@endsection
