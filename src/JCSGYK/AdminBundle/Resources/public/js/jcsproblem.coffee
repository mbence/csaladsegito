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
                HBlocks.scrollTo(4)

                # start the ajax request
                $.post($("#geteventform").attr("action"), {id: $(this).data("eventid")}, (data) ->
                    $("#eventblock .loading").hide()
                    $("#eventblock .eventcontent").html(data).show()
                    JcsToggle.init("eventblock")
                    HBlocks.scrollTo(4)
                ).error( (data) ->
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $("#eventblock .loading").hide()
                    HBlocks.closeBlock(4)
                )
                $("#event-list tbody tr").removeClass("current cursor")
                $(this).addClass("current cursor")

            false
        )