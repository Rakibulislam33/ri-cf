jQuery(document).ready(function($){
    $('#ri-cf-form').on('submit', function(e){
        e.preventDefault();

        var $form = $(this);
        var data = {
            action: 'ri_cf_submit',
            nonce: ri_cf_obj.nonce,
            name: $.trim($('#ri_name').val() || ''),
            email: $.trim($('#ri_email').val() || ''),
            phone: $.trim($('#ri_phone').val() || ''),
            message: $.trim($('#ri_message').val() || '')
        };

        // simple client-side validation
        if (!data.name || !data.email || !data.message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error','Please fill required fields.','error');
            } else {
                alert('Please fill required fields.');
            }
            return;
        }

        $('#ri-cf-submit').attr('disabled', true);

        $.post(ri_cf_obj.ajax_url, data, function(resp){
            $('#ri-cf-submit').attr('disabled', false);
            if (resp && resp.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Success', resp.data.message, 'success');
                } else {
                    alert(resp.data.message);
                }
                $form[0].reset();
            } else {
                var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Submission failed.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', msg, 'error');
                } else {
                    alert(msg);
                }
            }
        }).fail(function(){
            $('#ri-cf-submit').attr('disabled', false);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'AJAX error. Try again.', 'error');
            } else {
                alert('AJAX error. Try again.');
            }
        });
    });
});
