/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(function(){
    var $ = jQuery;
    $("#add_poll_answer").click(function(){
        console.log($(this).parent('fieldset').find('label'));
        var html=$(this).parent('fieldset').find('label').html();
        
        $(this).parent('fieldset').append('<label class="block">'+html+'</label><br />');
    });
});