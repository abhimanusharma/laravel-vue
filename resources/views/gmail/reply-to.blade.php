@extends('gmail.layout.master')

@section('container')
    <div class="row">

        <div class="col-8 offset-2">

            @include('gmail.common.dashboard-back')

            <div class="card">
                <div class="card-body">

                    @include('gmail.common.email-form')


                    <br />

                    <span style="font-size: 2em; cursor: pointer; " class="mdi mdi-message-minus text-warning"></span>
                    <div style="width: 100%; border-top: 3px solid #ffc107;" >

                        @include('gmail.common.email-body')

                        @include('gmail.common.email-attachment')

                    </div>




                </div>
            </div>
        </div>

    @include('gmail.common.email-editor-scripts', ['emailAreaId'=>'gmailEmailArea'])

@endsection
