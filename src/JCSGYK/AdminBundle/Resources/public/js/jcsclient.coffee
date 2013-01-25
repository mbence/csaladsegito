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

        @initProblems()

        true

    initProblems: ->
        $("#showAllProblem").click ->
            JcsClient.toggleClosed()

        coo = JSON.parse($.cookie("jcsgyk"))
        coo.shCl ?= false
        $("#showAllProblem").attr('checked', true)
        if not coo.shCl
            $("#showAllProblem").click()
        # count closed problems
        n = 0
        $("#problem-list tr").each ->
            if $(this).data("isactive") == 0
                n++
        if n
            $("#showAllProblem").next().append(" (+" + n + ")")

        @setupProblems()

    toggleClosed: ->
        $("#problem-list tr").each ->
            if $(this).data("isactive") == 0
                $(this).toggle()

        coo = JSON.parse($.cookie("jcsgyk"))
        coo.shCl = if $("#showAllProblem").attr('checked') then true else false
        $.cookie("jcsgyk", JSON.stringify(coo), { expires: 365, path: '/' })

    saveToggles: ->
        tg_status = []
        n = 0
        $("#clientblock .togglable span").each ->
            tg_status[n] = $(this).hasClass("collapsed")
            n++

        coo = JSON.parse($.cookie("jcsgyk"))
        coo ?= {}
        coo.toggles = tg_status
        $.cookie("jcsgyk", JSON.stringify(coo), { expires: 365, path: '/' })

    setupProblems: ->
        $("#problem-list tbody tr").click( (event) ->
            event.stopPropagation()
            if $(this).data("problemid")?
                $("#problemblock .loading").show()
                $("#problemblock .problemcontent").hide()
                $("#problemblock").show()
                HBlocks.setBlockSizes()
                HBlocks.scrollTo(2)

                # start the ajax request
                $.post($("#getproblemform").attr("action"), {id: $(this).data("problemid")}, (data) ->
                    $("#problemblock .loading").hide()
                    $("#problemblock .problemcontent").html(data).show()
                    #JcsClient.init()
                ).error( (data) ->
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $("#problemblock .loading").hide()
                    $("#problemblock .close").click()
                )
                $("#problem-list tbody tr").removeClass("current")
                $(this).addClass("current")

            false
        )