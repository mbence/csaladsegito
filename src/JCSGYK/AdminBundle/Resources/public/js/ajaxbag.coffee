AjaxBag =
    init: ->
        $(".flashbag div").css('marginLeft', ->
            return -1 *( $(this).outerWidth() / 2)
        ).delay(4000).fadeOut(3000)

    showLoader: ->
        $(".ajaxbag .ajax-loader").css('marginLeft', -1 * ($(".ajaxbag .ajax-loader").outerWidth() / 2)).show()

    hideLoader: ->
        $(".ajaxbag .ajax-loader").hide()

    showNotice: (notice) ->
        AjaxBag.hideLoader()
        $(".ajaxbag .ajax-notice")
            .stop().clearQueue().html(notice)
            .css({
                'marginLeft': -1 * ($(".ajaxbag .ajax-notice").outerWidth() / 2),
                'opacity': 1
            })
            .show().delay(4000).fadeOut(3000)

    hideNotice: ->
        $(".ajaxbag .ajax-notice").stop().clearQueue().hide()