jQuery(document).ready(function($) {
    $('#ri-cf-form').on('submit', function(e) {
        e.preventDefault();

        $.post(ricf_ajax.url, {
            action: 'ricf_submit_form',
            _ajax_nonce: ricf_ajax.nonce,
            name: $('input[name="name"]').val(),
            email: $('input[name="email"]').val(),
            phone: $('input[name="phone"]').val(),
            message: $('textarea[name="message"]').val(),
        }, function(response) {
            if(response.success) {
                $('#ricf-response').html('<p>' + response.data + '</p>');
                $('#ri-cf-form').remove(); // remove form after submit
            }
        });
    });
});
