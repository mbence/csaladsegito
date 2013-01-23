###
    Sets up the client block actions
###
JcsClient =
    init: ->
        $(".togglable").prepend("<span></span>").on "click", (event) ->
            if $(this).next().is(":visible")
                if event.isTrigger?
                    $(this).next().hide()
                else
                    $(this).next().slideUp(200, 'linear')
                $("span", this).addClass("collapsed")
            else
                if event.isTrigger?
                    $(this).next().show()
                else
                    $(this).next().slideDown(200, 'linear')
                $("span", this).removeClass("collapsed")
            JcsClient.saveToggles()

        # init toggles
        coo = JSON.parse($.cookie('jcsgyk'))
        n = 0
        $("#clientblock .togglable").each ->
            if coo.toggles[n]
                $(this).click()
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
