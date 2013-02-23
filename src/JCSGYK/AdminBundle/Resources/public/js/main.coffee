###
    Init all js elements
###
$ ->

    JcsMenu.init()
    AjaxBag.init()
    # TODO: only start the appropriate init!
    JcsSearch.init()
    HBlocks.init()
    JcsAdmin.init()
    JcsChart.init()

    JcsSearch.qSubmit()



    $("body").ajaxComplete (event, XMLHttpRequest, ajaxOption) ->
        if XMLHttpRequest.getResponseHeader('x-debug-token')
            $('.sf-toolbar').empty()
            $.get window.location.protocol+'//'+window.location.hostname+'/app_dev.php/_wdt/'+XMLHttpRequest.getResponseHeader('x-debug-token'), (data) ->
                $('.sf-toolbar').append(data)
