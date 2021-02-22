<div class="row mb-5">
<div class="col-10 offset-1">
    <div class="card">
        <div class="card-body bg-light">

            <div class="row">
                <div class="col-9"><h5>{{$message['subject']}}</h5></div>
                <div class="col-3">
                    @foreach($message['labels'] as $label)
                        <span class="badge bg-secondary">{{$label}}</span>
                    @endforeach
                </div>
            </div>


            @include('gmail.common.email-header')

            <br />
            <hr/>

            @include('gmail.common.email-body')

        </div>
    </div>

    @include('gmail.common.email-attachment')


</div>
</div>
