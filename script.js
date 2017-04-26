jQuery(function () {
    var $ = jQuery;
    $("form.poll_edit #add-answer").click(function () {
        const $elm = $('form.poll_edit .form-group.new-answer');
        const $clone = $elm.eq(0).clone();
        $clone.find('input').val('');
        $elm.last().after($clone);
    });

    $('.polls .poll input[type="text"]').on('input', function () {
        if ($(this).val() == '') {
            $(this).siblings()
                .find('input[type="radio"]')
                .prop('disabled', false);
        } else {
            $(this).siblings()
                .find('input[type="radio"]')
                .prop('disabled', true);
        }
    });

});
