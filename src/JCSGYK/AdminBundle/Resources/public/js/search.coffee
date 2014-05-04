JcsSearch =
    qto: null
    init: ->
        orig_results_text = $("#search-results").html()

        setActive = $("#clientblock .clientcontent").text() == ""

        # search field
        nf = new NiceField($("#quicksearch #q"), {
            clearHook: =>
                @qSubmit()
            onChange: (event) =>
                clearTimeout(@qto)
                if 13 == event.which
                    event.stopPropagation()
                    event.preventDefault()
                    @qSubmit()
                else
                    @qto = setTimeout( =>
                        @qSubmit()
                    , 300)

                true
            focus: setActive
            select: setActive
        })

        # quick search
        $("#quicksearch").submit( ->
            nf.start()
            HBlocks.scrollTo(1)
            if $("#quicksearch #q").val() == ''
                $("#search-results").html(orig_results_text)
            search_url = $(this).attr("action")
            if $("#q").val() then search_url += '?q=' + encodeURIComponent($("#q").val())
            $.get(search_url, (data) ->
                nf.stop()
                if $("#quicksearch #q").val() != ''
                    # display search results
                    $("#search-results").html(data)
                    # bind click events on the results
                    JcsSearch.setupResults()
            ).error( (data) ->
                nf.stop()
                # there was some error :(
                AjaxBag.showError(data.statusText)
            )

            false
        )

        # new client button
        JcsClient.initButtonRow()

        if $("#clientblock .clientcontent").text() == "" and $("#quicksearch #q").val() != ''
            JcsSearch.qSubmit()

    qSubmit: ->
        $("#quicksearch").submit();

    setupResults: ->
        $("#search-results tbody tr").click( (event) ->
            event.stopPropagation()
            $("#clientblock .loading").show()
            $("#clientblock .clientcontent").hide()
            $("#clientblock").show()
            HBlocks.closeBlock(4)
            HBlocks.closeBlock(3)
            HBlocks.scrollTo(2)

            # start the ajax request
            client_url = $("#getclientform").attr("action") + '/' + $(this).data("userid")
            $.get(client_url, (data) ->
                $("#clientblock .loading").hide()
                $("#clientblock .clientcontent").html(data).show()
                JcsClient.init()
            ).error( (data) ->
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $("#clientblock .loading").hide()
                HBlocks.closeBlock(2)
            )
            $("#search-results tr").removeClass("current cursor")
            $(this).addClass("current cursor")
        )

        # check for results number and click tr if only 1
        if $("#search-results tbody tr").size() == 1
            $("#search-results tbody tr").eq(0).click()

        # highlight the search keywords
        keywords = $("#quicksearch #q").val().split(" ")
        $("#search-results").highlight key for key in keywords
        
        true
