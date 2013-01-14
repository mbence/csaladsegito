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
            if $("#quicksearch #q").attr('value') == ''
                $("#search-results").html(orig_results_text)
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                nf.stop()
                if $("#quicksearch #q").attr('value') != ''
                    # display search results
                    $("#search-results").html(data)
                    # bind click events on the results
                    _this.setupResults()
            )

            false
        )

    qSubmit: ->
        $("#quicksearch").submit();

    setupResults: ->
        $("#search-results tbody tr").click( ->
            $("#personblock .loading").show()
            $("#personblock .personcontent").hide()
            $("#personblock").show()
            HBlocks.setBlockSizes()

            # start the ajax request
            $.post($("#getpersonform").attr("action"), {id: $(this).data("userid")}, (data) ->
                $("#personblock .loading").hide()
                $("#personblock .personcontent").html(data).show()
            )
            $("#search-results tr").removeClass("current")
            $(this).addClass("current")
        )

        # check for results number and click tr if only 1
        if $("#search-results tr").size() == 2
            $("#search-results tr").eq(1).click()