JcsSearch =
    qto: null
    init: ->
        orig_results_text = $("#search-results").html()

        # search field
        nf = new NiceField($("#quicksearch #q"), {
            clearHook: =>
                @qSubmit()
            onChange: =>
                clearTimeout(@qto)
                @qto = setTimeout( =>
                    @qSubmit()
                , 300)

                true
        })

        # quick search
        $("#quicksearch").submit( ->
            nf.start()
            HBlocks.scrollTo(1)
            if $("#quicksearch #q").attr('value') == ''
                $("#search-results").html(orig_results_text)
            search_url = $(this).attr("action")
            if $("#q").attr('value') then search_url += '/' + $("#q").attr('value')
            $.get(search_url, (data) ->
                nf.stop()
                if $("#quicksearch #q").attr('value') != ''
                    # display search results
                    $("#search-results").html(data)
                    # bind click events on the results
                    _this.setupResults()
            ).error( (data) ->
                nf.stop()
                # there was some error :(
                AjaxBag.showError(data.statusText)
            )

            false
        )

        # new client button
        JcsClient.initButtonRow()

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
                $("#clientblock .clientcontent").data("url", client_url)
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