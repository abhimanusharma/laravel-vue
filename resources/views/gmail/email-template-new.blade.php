@extends('gmail.layout.master')

@section('container')

    @if(session('success'))
        <div class="row">
            <div class="col-8 offset-2">
                <div class="alert alert-success">{{session('success')}}</div>
            </div>
        </div>

    @endif

    <div class="row">

        <div class="col-2 offset-1">
            <br />
            <br />
            <div class="card">
                <div class="card-body">
                    <form>
                        @csrf
                    <ul class="list-group mt-4">

                        @if($templates->count()>0)

                            @foreach($templates as $template)

                        <li class="list-group-item d-flex justify-content-between
                        align-items-center bg-info mb-1"><a style="text-decoration: none; width: 100%; color:#fff;"
                                                            class="template-leader" data-template-id="{{$template->id}}"  href="#">{{$template->name}}</a></li>

                            @endforeach

                            @endif

                    </ul>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-8">

            <a style="color:#888; font-size: 1.5em; " href="{{route('dashboard')}}"><span class="mdi
            mdi-arrow-left-drop-circle"></span></a>

            <div class="card">
                <div class="card-header bg-secondary text-white">
                    New Template
                </div>



                <div class="card-body">
                    <form action="{{route('gmail.saveETemplate')}}" method="post">
                        @csrf

                        <div class="row">
                            <div class="col">

                                <div class="mb-3">
                                    <input type="text" name="template_name"
                                           class="form-control {{$errors->has('template_name')?'is-invalid':''}}"
                                           id="template_name"
                                           placeholder="Template Name" value="">

                                    @if($errors->has('template_name'))
                                        <span class="invalid-feedback">{{$errors->first('template_name')}}</span>
                                    @endif


                                </div>


                                <div class="mb-3">
                        <textarea class="form-control" name="template"
                                  id="gmailEmailArea"></textarea>

                                    @if($errors->has('template'))
                                        <span
                                            style="color:#dc3545; padding-top:5px; display: block;">{{$errors->first('template')}}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">


                                <button type="submit" class="btn btn-success">Save Template</button>
                            </div>
                        </div>


                    </form>

                </div>
            </div>
        </div>
    </div>

    @include('gmail.common.email-editor-scripts', ['emailAreaId'=>'gmailEmailArea', 'areaType'=>'template.new'])





@endsection
