
@php $areaType = !empty($areaType) ? $areaType :'';  @endphp

<script src="{{asset('tinymce/tinymce.min.js')}}" referrerpolicy="origin"></script>


<script>

    var customPlugins, customToolbar, customHeight, areaType = '{{$areaType}}';

    if( areaType === 'template.new'){
        customPlugins = [
            'advlist autolink lists link image charmap print preview anchor table code fullscreen',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ];

        customToolbar = 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | indent outdent | bullist numlist  | ' +
            'table tableinsertdialog tablecellprops tableprops | fullscreen | code';

        customHeight = 500;
    }else{
        customPlugins = [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ];

        customToolbar = 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ';

        customHeight = 400;
    }



    tinymce.init({
        selector: '{{'#'.$emailAreaId}}',
        menubar: false,
        plugins: customPlugins,
        toolbar: customToolbar,
        height: customHeight,
        setup: function (editor) {
            editor.on('init', function () {
                document.querySelector('.tox-statusbar__branding').setAttribute('style', 'display:none');
            });
        }
    });


</script>
