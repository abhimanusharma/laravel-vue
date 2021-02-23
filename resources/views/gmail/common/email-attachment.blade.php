@if(!empty($message['has_attachments']))
    <br />

    <div class="card">
        <div class="card-header bg-warning text-white">Attachments</div>
        <div class="card-body">
            <div class="row">
                <div class="col-12">


                    @foreach($message['attachments'] as $attachment)

                        @if(in_array($attachment['type'], ['png', 'jpeg', 'jpg']))
                            <a target="_blank" href="{{asset($attachment['path'])}}"><img src="{{asset
                            ($attachment['path'])}}" width="150"></a>
                        @else

                            <a target="_blank" href="{{asset($attachment['path'])}}" style="color: #4f5050;
"><span style="font-size: 5em;" class="mdi mdi-file-document"></span>
                        @endif
                                <p>{{$attachment['name']}}</p>
                    @endforeach


                </div>
            </div>
        </div>
    </div>
@endif
