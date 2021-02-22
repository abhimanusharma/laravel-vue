<a href="{{route('gmail.new')}}" class="btn btn-warning"><span class="mdi mdi-email-send"></span> Compose</a>
&nbsp;
&nbsp;
&nbsp;
&nbsp;
<a href="{{route('gmail.createETemplate')}}" class="btn btn-success"><span class="mdi mdi-page-previous"></span> Create Email Template</a>

<ul class="list-group mt-4">
    @foreach($data['folders'] as $key => $folder)
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <a style="text-decoration: none; width: 100%; color:#bb2d3b; " href="{{route('dashboard', ['type'=>$key])}}">
            <span class="mdi mdi-email-mark-as-unread"></span> {{$folder}}
        </a>
        @if($key===$data['current_folder'])
        <span class="badge bg-primary rounded-pill">unread {{$data['unreadNumber']}}</span>
            @endif
    </li>
    @endforeach
</ul>

<br />
<br />

<form action="{{route('gmail.createLabel')}}" method="POST" class="row">
    @csrf
    <div class="col-6">
        <label for="LabelName" class="visually-hidden">Label Name</label>
        <input type="text" class="form-control {{$errors->has('LabelName')?'is-invalid':''}}" id="LabelName"
               name="labelName"
               placeholder="Label Name">
    </div>
    <div class="col-6">
        <button type="submit" class="btn btn-warning mb-1">Create Label</button>
    </div>
    @if($errors->has('LabelName'))
        <span style="color: #dc3545; font-size: 0.9em;">{{$errors->first('LabelName')}}</span>
    @endif
</form>



