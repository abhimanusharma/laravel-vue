jQuery(function ($) {

    var gmailAttachArea = $("#gmailAttachmentArea");
    gmailAttachArea.hide();

    $("#gmailAttachment").on("change", function (e) {
        const files = this.files;

        if (files.length > 0) {
            gmailAttachArea.show();
            gmailAttachArea.find(".progress").hide();


            var form = $(this).closest("form");
            var token = form.find('input[name=_token]').val();
            var message_id = form.find('input[name=message_id]');


            var formData = new FormData();
            for (let i = 0; i < files.length; i++) {

                formData.append('attachments[]', files[i]);
            }
            formData.append("_token", token)
            formData.append("message_id", message_id.val())

            $.ajax({
                url: '/compose/attachment/save',
                type: 'POST',
                dataType: 'json',
                processData: false,
                contentType: false,
                data: formData,
                success: function (data, status) {
                    console.log([data, status]);

                    if (data.status === 'success') {
                        var id = data.message_id;
                        message_id.val(id)


                        if (data.attachments) {

                            gmailAttachArea.find('.row').remove();

                            for(i in data.attachments){
                                let template = `<div class="row">
                                        <div class="col-11"><a class="attachedFile" target="_blank" href="${data.attachments[i]}">${i}</a></div>
                                        <div class="col-1">
                                            <div class="mdi mdi-close-box-outline detachedFile"></div>
                                        </div>
                                    </div>`;
                                gmailAttachArea.append(template);
                            }

                        }


                        // gmailAttachArea.find('.attachedFile');

                    }

                },
                error: function (jqxhr, status, strerror) {
                    console.log(['error',status, strerror]);
                },
                xhr: function () {
                    var myXhr = $.ajaxSettings.xhr();
                    if (myXhr.upload) {
                        myXhr.upload.addEventListener('progress', progress, false);
                    }
                    return myXhr;
                },
            });

            function progress(e) {
                $("#gmailAttachmentArea .progress").show();

                if (e.lengthComputable) {
                    var max = e.total;
                    var current = e.loaded;

                    var Percentage = (current * 100) / max;
                    Percentage = Math.round(Percentage);

                    gmailAttachArea.find(".progress-bar").attr('style', "width: " + Percentage + "%");
                    gmailAttachArea.find(".progress-bar").text(Percentage + "%");

                    if (Percentage >= 100) {

                    }
                }

            }


        }

    });

    $(document).on("click",".detachedFile", function (e){
        var form = $(this).closest('form');
        var row = $(this).closest('.row');
        data ={};
        data.file = row.find('.attachedFile').text();
        data._token = form.find('input[name=_token]').val();
        data.message_id = form.find('input[name=message_id]').val();

        // console.log(data);

        $.ajax({
            url:'/compose/attachment/delete',
            type:'POST',
            dataType:'json',
            data,
            success:function(data, status){

                if(data.status==='success'){
                    row.remove();
                }

            },
            error:function (jqxhr, error, err_string){
                console.log([error, err_string]);
            }
        });




    });

    $("#gmailSaveDraft").on("click", function (e) {

        $('.messagebox').hide();

        var form = $(this).closest('form');
        data = {};
        data.to = form.find('input[name=to]').val();
        data.subject = form.find('input[name=subject]').val();
        data.message = tinymce.get('gmailEmailArea').getContent();
        data._token = form.find('input[name=_token]').val();
        data.message_id = form.find('input[name=message_id]').val();

        if (data.subject !== '' || data.message !== '' || data.to !== '') {

            $.ajax({
                url: '/compose/save',
                type: 'POST',
                dataType: 'json',
                data,
                success: function (data, status) {
                    console.log([data, status]);

                    if(data.status==='success'){
                        window.location.href=data.route;
                    }

                    if(data.status==='error'){
                        $('.messagebox').text(data.message);
                        $('.messagebox').show();
                    }

                },
                error: function (jqxhr, error, str_error) {
                    console.log([jqxhr, error, str_error]);

                }
            });


        } else {

            $('.messagebox').text("Can't save empty draft");
            $('.messagebox').show();
        }


    });


    const templateLoader = (type, data)=>{
        $.ajax({
            url:'/load-email-template',
            type:'POST',
            dataType:'json',
            data,
            success:function (data, status){
                console.log([data, status]);

                if(data.status ==='success'){

                    if(type==='template'){
                        $("form input[name='template_name']").val(data.data.name);
                    }

                    tinymce.get('gmailEmailArea').setContent(data.data.body);

                }

            },
            error:function(jqxhr, status, str_error){
                console.log([status, str_error]);
            }
        });

    }


    $('.select-email-template').on('change', function(e){
        const data ={};
        const element = $(e.currentTarget);

        if(element.val()==='0'){
            tinymce.get('gmailEmailArea').setContent('');
            return false;
        }

        data.templateId = element.val();
        data._token = element.closest('form').find('[name=_token]').val();

        templateLoader('compose', data);
    });




    $('.template-leader').on('click', function(e){
        e.preventDefault();
        const data ={};
        const element = $(e.currentTarget);
        data.templateId = element.attr('data-template-id');
        data._token = element.closest('form').find('[name=_token]').val();

        templateLoader('template', data);
    });

});








