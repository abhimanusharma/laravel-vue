@if(!empty($data['folders']))
    <form action="{{route('gmail.moveFolder')}}" method="POST"  class="row">
        @csrf
        <input type="hidden" name="message_id" value="{{$data['message_id']}}" />
        <input type="hidden" name="current_folder" value="{{$data['current_folder']}}" />
        <div class="col-6">
            <select name="folder" class="form-select">
                <option value="" selected>-- Select Folder --</option>
                @foreach($data['folders'] as $key =>$folder)
                    <option value="{{$key}}">{{$folder}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6">
            <button type="submit" class="btn btn-secondary"><span class="mdi
                    mdi-content-save-move-outline"></span>
                Move</button>
        </div>
    </form>
@endif
