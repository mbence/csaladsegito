###
    Sets up the client block actions
###
JcsToggle =
    init: (block) ->
        # init toggles
        toggles = JcsOpt.get("toggles_" + block)
        n = 0
        $("#" + block + " .togglable").prepend("<span></span>").each ->
            if toggles[n] == 0
                $(this).next().hide()
                $("span", this).addClass("collapsed")
            n++

        $("#" + block + " .togglable").on "click", (event) ->
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

    saveToggles: (block) ->
        tg_status = []
        n = 0
        $("#" + block + " .togglable span").each ->
            tg_status[n] = if $(this).hasClass("collapsed") then 0 else 1
            n++
        # save toggle status
        JcsOpt.set("toggles_" + block, tg_status)

    multiselect: (parent) ->
        $("div.multiselect", parent).off("click").on("click", (event) ->
            event.stopPropagation()
        )
        $("div.multiselect .multihead", parent).off("click").on("click", ->
            $(this).parent().toggleClass("active")
        )
        $("div.multiselect input", parent).on("focus", ->
            $(this).parent().addClass("active")
        )

    multiselectOff: ->
        $("div.multiselect").removeClass("active")