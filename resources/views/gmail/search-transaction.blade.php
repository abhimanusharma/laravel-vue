<form action="{{route('gmail.searchByTransaction')}}" method="post" class="row g-3">
    @csrf
    <div class="col-4 offset-4">
        <input type="text" class="form-control {{$errors->has('transaction_id')?'is-invalid':''}}" name="transaction_id"
               id="inputPassword2"
               placeholder="Transaction Id">
        @if($errors->has('transaction_id'))
            <span class="invalid-feedback">{{$errors->first('transaction_id')}}</span>
            @endif
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary mb-3">Search</button>
    </div>
</form>
<br />
