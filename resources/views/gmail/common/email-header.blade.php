<div class="row">
    <div class="col-12">
        <ul class="list-group">
            <li class="list-group-item"><span>From:
            </span><strong>{{$message['from_with_email']['name']}}</strong> &lt;{{$message['from_with_email']['email']}}&gt;</li>
            <li class="list-group-item"><span>To: </span>
                @foreach($message['to'] as $to)
                    {{$to['name']}}&lt;{{$to['email']}}&gt;
                @endforeach
            </li>
            <li class="list-group-item"><span>Date: </span>{{$message['date_time']}} | ({{$message['time_passed']}})</li>
            <li class="list-group-item"><span>Subject: </span>{{$message['subject']}}</li>
            <li class="list-group-item"><span>Reply-To: </span>{{$message['reply_to']['name']}}&lt;
                {{$message['reply_to']['email']}}&gt;</li>
        </ul>
    </div>
</div>
