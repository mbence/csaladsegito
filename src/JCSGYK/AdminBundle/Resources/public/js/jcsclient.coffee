###
    Sets up the client block actions
###
JcsClient =
    init: ->
        # init toggles
        JcsToggle.init("clientblock")
        HBlocks.setCloseButtons()
        @initProblems()

        true

    initProblems: ->
        $("#showAllProblem").click (event) ->
            JcsClient.toggleClosed()
            # save the cookie only if clicked by the user
            if not event.isTrigger?
                showCl = if $("#showAllProblem").attr('checked') then 1 else 0
                JcsOpt.set("shCl", showCl)

        showClosed = JcsOpt.get("shCl")
        $("#showAllProblem").attr('checked', true)
        # clicking the chekcbox hides the closed problems
        if not showClosed
            $("#showAllProblem").click()

        # count closed problems
        n = 0
        $("#problem-list tr").each ->
            if $(this).data("isactive") == 0
                n++
        if n
            $("#showAllProblem").next().append(" (+" + n + ")")

        @setupProblems()

    toggleClosed: ()->
        $("#problem-list tr").each ->
            if $(this).data("isactive") == 0
                $(this).toggle()

    setupProblems: ->
        $("#problem-list tbody tr").click( (event) ->
            event.stopPropagation()
            if $(this).data("problemid")?
                HBlocks.closeBlock(4)
                $("#problemblock .loading").show()
                $("#problemblock .problemcontent").hide()
                $("#problemblock").show()
                HBlocks.setBlockSizes()
                HBlocks.scrollTo(3)

                # start the ajax request
                $.post($("#getproblemform").attr("action"), {id: $(this).data("problemid")}, (data) ->
                    $("#problemblock .loading").hide()
                    $("#problemblock .problemcontent").html(data).show()
                    HBlocks.scrollTo(3)
                    JcsProblem.init()
                ).error( (data) ->
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $("#problemblock .loading").hide()
                    HBlocks.closeBlock(3)
                )
                $("#problem-list tbody tr").removeClass("current cursor")
                $(this).addClass("current cursor")

            false
        )