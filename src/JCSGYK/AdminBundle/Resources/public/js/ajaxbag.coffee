###
    Set up and show/hide the ajax and symfony flash notifications and loader status

    Usage:
        AjaxBag.showLoader()
        ...
        AjaxBag.showNotice('Hello')
###

AjaxBag =
    init: ->
        $(".flashbag div")
            .css 'marginLeft', ->
                return -1 *( $(this).outerWidth() / 2)
            .delay(4000).fadeOut(3000)
            .on "click", ->
                $(this).stop().clearQueue().hide()

        $(".ajaxbag div").on "click", ->
            $(this).stop().clearQueue().hide()

    # centers and displays the ajax loader div
    showLoader: ->
        $(".ajaxbag .ajax-loader").css('marginLeft', -1 * ($(".ajaxbag .ajax-loader").outerWidth() / 2)).show()

    # hides the loader gif
    hideLoader: ->
        $(".ajaxbag .ajax-loader").hide()

    # displays the ajax notice, and starts the auto-fade out
    showNotice: (notice, cl = false) ->
        @hideLoader()
        $(".ajaxbag .ajax-notice")
            .addClass(cl)
            .stop().clearQueue().html(notice)
            .css({
                'marginLeft': -1 * ($(".ajaxbag .ajax-notice").outerWidth() / 2),
                'opacity': 1
            })
            .show().delay(4000).fadeOut(3000, ->
                $(this).removeClass(cl)
            )

    # displays an error notice
    showError: (msg) ->
        @showNotice(msg, 'ajax-error')

    # hides the ajax notice
    hideNotice: ->
        $(".ajaxbag .ajax-notice").stop().clearQueue().hide()