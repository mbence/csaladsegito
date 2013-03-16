###
    Sets up the client block actions
###
JcsClient =
    init: ->
        # init toggles
        JcsToggle.init("clientblock")
        HBlocks.setCloseButtons()
        $("#clientblock").show()
        HBlocks.setBlockSizes()

        @initButtonRow()
        @initForm()
        @initProblems()
        true

    initForm: ->
        # count the current utilityproviders we have (e.g. 2), use that as the new
        # index when inserting a new item (e.g. 2)
        $(".utilityproviders").data('index', $(".utilityproviders").find('tr').length);
        $(".add_utilityprovider").on 'click', (e) =>
            # prevent the link from creating a "#" on the URL
            e.preventDefault()

            # add a new tag form (see next code block)
            @addTagForm($(".utilityproviders"))

            false

        # client edit
        $("#client_edit").submit ->
            $(".save_client").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                $("#clientblock .clientcontent").html(data).show()
                # display the result message
                if $("#result").data("result-notice")
                    AjaxBag.showNotice($("#result").data("result-notice"))
                if $("#result").data("result-error")
                    AjaxBag.showError($("#result").data("result-error"))
                JcsClient.init()
            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $(".save_client").removeClass('animbutton')
            )

            false

        # textarea auto height
        $("#client_note").elastic()


    addTagForm: (collectionHolder) ->
        prototype = collectionHolder.data('prototype')
        index = collectionHolder.data('index')

        # Replace '__name__' in the prototype's HTML to
        # instead be a number based on how many items we have
        newForm = prototype.replace(/__name__/g, index)

        # increase the index with one for the next item
        collectionHolder.data('index', index + 1)

        # Display the form in the page in an li, before the "Add a tag" link li
        collectionHolder.append(newForm)

    initButtonRow: ->
        # get buttons
        $(".edit_client").add(".back_to_view").add(".new_client").click (event) ->
            event.stopPropagation()
            if !$(this).hasClass('animbutton')
                $(this).addClass('animbutton')

                $.get($(this).attr("href"), (data) =>
                    $(this).removeClass('animbutton')
                    $("#clientblock .clientcontent").html(data).show()
                    JcsClient.init()

                ).error( (data) =>
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $(this).removeClass('animbutton')
                )
            false

        $(".new_client").click (event) ->
            $("#clientblock .clientcontent").hide()
            HBlocks.closeBlock(4)
            HBlocks.closeBlock(3)
            false

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