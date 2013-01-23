###
    Sets up the client block actions
###
JcsClient =
    init: ->
        $(".togglable").prepend("<span></span>").on "click", ->
            if $(this).next().is(":visible")
                $(this).next().hide()
                $("span", this).addClass("collapsed")
            else
                $(this).next().show()
                $("span", this).removeClass("collapsed")
            JcsClient.saveToggles()

        # init toggles
        coo = JSON.parse($.cookie('jcsgyk'))
        n = 0
        $("#clientblock .togglable").each ->
            console.log coo.toggles[n]
            if coo.toggles[n]
                $(this).click()
#                $(this).addClass("collapsed")
            n++


    saveToggles: ->
        tg_status = []
        n = 0
        $("#clientblock .togglable span").each ->
            tg_status[n] = $(this).hasClass("collapsed")
            n++

        coo = JSON.parse($.cookie('jcsgyk'))
        coo ?= {}
        coo.toggles = tg_status
        $.cookie('jcsgyk', JSON.stringify(coo), { expires: 365, path: '/' })
