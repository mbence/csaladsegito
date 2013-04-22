###
    Init all js elements
###
$ ->

    JcsMenu.init()
    AjaxBag.init()

    if $(".task-list").length
        JcsTask.init()

    # client frames
    if $(".contentscroller").length
        HBlocks.init()

    # search field
    if $(".quicksearch").length
        JcsSearch.init()
        if $("#clientblock .clientcontent").text() == ""
            JcsSearch.qSubmit()

    # client block
    if $("#clientblock .clientcontent").text() != ""
        JcsClient.init()

    # problem block
    if $("#problemblock .problemcontent").text() != ""
        JcsProblem.init()

    if $(".settings").length
        JcsSettings.init()

    if $(".chart").length
        JcsChart.init()

    JcsWebDebug.init()

###
  Web dev toolbar ajax refresh
###
JcsWebDebug =
    init: ->
        $("body").ajaxComplete (event, XMLHttpRequest, ajaxOption) ->
            if $('.sf-toolbar').length and XMLHttpRequest.getResponseHeader('x-debug-token')
                $('.sf-toolbar').empty()
                $.get window.location.protocol+'//'+window.location.hostname+'/app_dev.php/_wdt/'+XMLHttpRequest.getResponseHeader('x-debug-token'), (data) ->
                    $('.sf-toolbar').append(data)
