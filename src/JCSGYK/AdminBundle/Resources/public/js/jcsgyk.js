JCS = {
    // quicksearch timeout
    qto: null,
    
    init: function() {
        $(".flashbag div").css('marginLeft', function(index) {
            return -1 *( $(this).outerWidth() / 2);
        }).delay(4000).fadeOut(3000);
        $(".ajaxbag div").hide();

        // add the inquiry ajax actions
        $(".inquiry a").click(function() {
            if (!$(this).hasClass('ajax-loading2') && $(this).attr('href')) {
                $(this).addClass('ajax-loading2');
                var that = this;
                $.post($(this).attr('href'), function(data) {
                    $(that).removeClass('ajax-loading2');
                    JCS.showNotice(data);
                });
            }
            return false;
        });
        
        // quick search
        $("#quicksearch #q").on('input', function(){
            clearTimeout(JCS.qto);
            JCS.qto = setTimeout('JCS.qSubmit()', 300);
        }).select().focus();
        $("#quicksearch").submit(function(){
            $("#quicksearch #q").addClass('ajax-loading3');
            $.post($(this).attr('action'), $(this).serialize(), function(data) {
                $("#quicksearch #q").removeClass('ajax-loading3');
                $("#search-results").html(data);
            });
            return false;
        });

    },
    
    qSubmit: function() {
        $("#quicksearch").submit();
    },
    
    showAjaxLoader: function() {    
        $(".ajaxbag .ajax-loader")
            .css('marginLeft', -1 * ($(".ajaxbag .ajax-loader").outerWidth() / 2))
            .show();
    },
    hideAjaxLoader: function() {  
        $(".ajaxbag .ajax-loader").hide();
    },    
    showNotice: function(notice) {
        JCS.hideAjaxLoader();
        $(".ajaxbag .ajax-notice")
            .stop()
            .clearQueue()
            .html(notice)
            .css({
                'marginLeft': -1 * ($(".ajaxbag .ajax-notice").outerWidth() / 2),
                'opacity': 1
            })
            .show()
            .delay(4000)
            .fadeOut(3000);
    },
    hideNotice: function() {
        $(".ajaxbag .ajax-notice")
            .stop()
            .clearQueue()
            .hide();
    }
}

// document ready
$(function() {
    JCS.init();
    
    //JCS.qSubmit();
});

