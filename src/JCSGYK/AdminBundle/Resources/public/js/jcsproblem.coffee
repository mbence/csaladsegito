###
    Sets up the client block actions
###
JcsProblem =
    init: ->
        # init toggles
        JcsToggle.init("problemblock")

        @setupEvents()

        true

    setupEvents: ->
        $("#event-list tbody tr").click( (event) ->
            event.stopPropagation()
            if $(this).data("eventid")?
                $("#eventblock .loading").show()
                $("#eventblock .eventcontent").hide()
                $("#eventblock").show()
                HBlocks.setBlockSizes()
                HBlocks.scrollTo(3)

                # start the ajax request
                $.post($("#geteventform").attr("action"), {id: $(this).data("eventid")}, (data) ->
                    $("#eventblock .loading").hide()
                    $("#eventblock .eventcontent").html(data).show()
                    JcsToggle.init("eventblock")
                    HBlocks.scrollTo(3)
                ).error( (data) ->
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $("#eventblock .loading").hide()
                    $("#eventblock .close").click()
                )
                $("#event-list tbody tr").removeClass("current")
                $(this).addClass("current")

            false
        )