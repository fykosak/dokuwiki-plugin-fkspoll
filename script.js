/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(function(){
    var $ = jQuery;
    $("#add_poll_answer").click(function(){
        var html=$(this).parent('.no').find('fieldset').find('label').html();
        
        $(this).parent('.no').find('fieldset').append('<br/><label>'+html+'</label>');
    });
});