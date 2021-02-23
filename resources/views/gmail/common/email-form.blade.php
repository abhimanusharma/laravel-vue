<form action="{{route($data['type'])}}" method="post">
    @csrf
    <div class="mb-3">
        <input type="text" name="to" class="form-control {{$errors->has('to')?'is-invalid':''}}"
               id="exampleFormControlInput1"
               placeholder="To" value="{{($data['type']==='gmail.reply'&& !empty($data['message']['from_with_email']))
               ?$data['message']['from_with_email']['email']:''}}">

        @if($errors->has('to'))
            <span class="invalid-feedback">{{$errors->first('to')}}</span>
        @endif

        @if(!empty($data['message']))
            <input type="hidden" name="message_id"/>
            <input type="hidden" name="thread_id" value="{{$data['message']['id']}}"/>
        @endif
    </div>

    <div class="mb-3">
        <input type="text" name="cc" class="form-control" placeholder="Cc" value="">
    </div>

    <div class="mb-3">
        <input type="text" name="bcc" class="form-control" placeholder="Bcc" value="">
    </div>


    @if(in_array($data['type'],['gmail.forward', 'gmail.reply', 'gmail.draft.send']))
        <div class="mb-3">
            <input type="{{$data['type']==='gmail.draft.send'?'text':'hidden'}}" name="subject" class="form-control"
                   value="{{$data['message']['subject']}}">
        </div>
    @elseif($data['type'] ==='gmail.send')
        <div class="mb-3">
            <input type="text" name="subject" class="form-control" placeholder="subject">
        </div>
    @endif

    <div class="mb-3">
        @if(!empty($data['templates']) && $data['templates']->count()>0)
            <select class="form-select select-email-template">
                <option value="0" selected>-- Select Email Template --</option>

                @foreach($data['templates'] as $template)
                    <option value="{{$template->id}}">{{$template->name}}</option>
                @endforeach

            </select>
        @endif

    </div>

    <div class="mb-3">
        <label for="gmailEmailArea" class="form-label">Message</label>

        @if($data['type'] ==='gmail.forward')
            @php $messageBody = $data['body']; @endphp
        @elseif($data['type'] ==='gmail.draft.send')
            @php $messageBody = $data['message']['body']; @endphp
        @else
            @php $messageBody = ''; @endphp
        @endif

        <textarea class="form-control" name="message"
                  id="gmailEmailArea">{!! $messageBody !!}</textarea>
    </div>
    <div class="row mb-3">
        <div class="col-2">
            <button type="submit" class="btn btn-primary"><span class="mdi mdi-email-send"></span>
                Send
            </button>
        </div>
        <div class="col-6">

            <div id="gmailAttachmentArea" class="alert
                                alert-primary">

                <div class="progress">
                    <div class="progress-bar"
                         role="progressbar"
                         style="width: 0%;"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%
                    </div>
                </div>
                <hr/>

            </div>


        </div>
        <div class="col-2">
            <div class="input-group">
                <input type="file" class="form-control visually-hidden" id="gmailAttachment"
                       multiple>
                <label class="input-group-text btn  btn-secondary btn-sm"
                       for="gmailAttachment"><span
                        class="mdi mdi-attachment"></span> Add Attachment</label>
            </div>
        </div>
        @if($data['type'] ==='gmail.send')
            <div class="col-2">
                <a id="gmailSaveDraft" class="btn btn-primary">Save Draft</a>
            </div>
        @endif
    </div>
</form>



