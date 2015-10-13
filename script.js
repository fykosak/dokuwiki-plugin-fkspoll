/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(function () {
    var $ = jQuery;
    $("#add_poll_answer").click(function () {

        var html = $(this).parent('fieldset').find('label').html();

        $(this).parent('fieldset').append('<label class="block">' + html + '</label><br />');
    });


    $(window).load(function () {
        $('#week_field').parent('label').hide();
        $('#date_field_from').parent('label').hide();
        $('#date_field_to').parent('label').hide();

    });


    $('input[name=time_type]').change(function () {

        if ($('#time_week').is(':checked')) {
            $('#week_field').parent('label').show();

        } else {
            $('#week_field').parent('label').hide();

        }

        if ($('#time_date').is(':checked')) {
            $('#date_field_from').parent('label').show();
            $('#date_field_to').parent('label').show();
        }
        else {
            $('#date_field_from').parent('label').hide();
            $('#date_field_to').parent('label').hide();
        }
    });
});