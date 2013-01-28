###
    Sets up the client block actions
###
JcsToggle =
    init: (block) ->
        $("#" + block + " .togglable").prepend("<span></span>").on "click", (event) ->
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
            JcsToggle.saveToggles(block)

        # init toggles
        toggles = JcsOpt.get("toggles_" + block)
        n = 0
        $("#" + block + " .togglable").each ->
            if toggles[n] == 0
                $(this).click()
            n++

    saveToggles: (block) ->
        tg_status = []
        n = 0
        $("#" + block + " .togglable span").each ->
            tg_status[n] = if $(this).hasClass("collapsed") then 0 else 1
            n++
        # save toggle status
        JcsOpt.set("toggles_" + block, tg_status)
