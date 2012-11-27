// document ready
$(function() {
    $(".flashbag .ajax-notice").hide();
    $(".flashbag .ajax-loader").css('marginLeft', function(index) {
        return -1 *( $(this).outerWidth() / 2);
    }).hide();
    
    // position and animate the flash messages
//    $(".flashbag div").css('marginLeft', function(index) {
//        return -1 *( $(".flashbag div").outerWidth() / 2);
//    }).delay(4000).fadeOut(3000);
    
    // add the inquiry ajax actions
    $(".inquiry a").click(function() {
        if ($(this).attr('href')) {
            $(".flashbag .ajax-loader").css('display', 'inline-block');
            $.post($(this).attr('href'), function(data) {
                $(".flashbag .ajax-loader").hide();
                $(".flashbag .ajax-notice").html(data).css('display', 'inline-block').css('marginLeft', function(index) {
                    return -1 *( $(this).outerWidth() / 2);
                }).delay(4000).fadeOut(3000);
            });
        }
        
        return false;
    });
});