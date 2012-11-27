// document ready
$(function() {
    // position and animate the flash messages
    $(".flashbag div").css('marginLeft', function(index) {
        return -1 *( $(".flashbag div").outerWidth() / 2);
    }).delay(4000).fadeOut(3000);
    
    // add the inquiry ajax actions
    $(".inquiry a").click(function() {
        console.log($(this).attr('href'));
        if ($(this).attr('href')) {
            $.post($(this).attr('href'), function(data) {
               console.log(data);
            });
        }
        
        return false;
    });
});