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
            if (!$(this).hasClass('disabled') && $(this).attr('href')) {
                $(this).addClass('disabled');
                var that = this;
                JCS.hideNotice();
                JCS.showAjaxLoader();
                $.post($(this).attr('href'), function(data) {
                    $(that).removeClass('disabled');
                    JCS.hideAjaxLoader();
                    JCS.showNotice(data);
                });
            }
            return false;
        });
        
        // quick search
        $("#quick_search #q").on('input', function(){
            clearTimeout(JCS.qto);
            JCS.qto = setTimeout('JCS.qSubmit()', 200);
        });
        $("#quick_search").submit(function(){
            $.post($(this).attr('action'), $(this).serialize(), function(data) {
                $("#search_results").html(data);
            });
            return false;
        });

    },
    
    qSubmit: function() {
        $("#quick_search").submit();
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
            .clearQueue()
            .stop()
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
            .clearQueue()
            .stop()
            .hide();
    }
}

// document ready
$(function() {
    
    JCS.init();
});

