/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(function () {
    var $ = jQuery;
    $("form.poll_edit .answers button.add_answer").click(function () {
      
        var $clone = $(this).siblings('label.new').eq(0).clone();

        $clone.find('input').val('');
        $(this).before($clone); 
      
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
    $(document).ready(function () {
        $('.polls .poll .answer .bar').each(function () {
            var w = $(this).data('percent');
        
            $(this).delay(2000)
                    .animate({width: w + "%"}, 2000, 'easeOutQuad');
        });

    });
});