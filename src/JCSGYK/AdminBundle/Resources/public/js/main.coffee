###
    Init all js elements
###
$ ->
    JcsMenu.init()
    AjaxBag.init()
    JcsSearch.init()
    HBlocks.init()

    JcsSearch.qSubmit()

    $("body").ajaxComplete( (event, XMLHttpRequest, ajaxOption) ->
        if XMLHttpRequest.getResponseHeader('x-debug-token')
            $('.sf-toolbarreset').remove()
            $.get(window.location.protocol+'//'+window.location.hostname+'/app_dev.php/_wdt/'+XMLHttpRequest.getResponseHeader('x-debug-token'), (data) ->
                $('body').append(data)
            )
    )
