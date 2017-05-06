"use strict";

jQuery(function () {
    var $ = jQuery;
    $('.poll').each(function () {
        const $poll = $(this);
        $poll.find('input[type="text"]').on('input', function () {
            if ($(this).val() == '') {
                $poll.find('input[type="radio"]')
                    .prop('disabled', false);
            } else {
                $poll.find('input[type="radio"]')
                    .prop('disabled', true)
                    .prop('checked', false);
            }
        })
    });
});
