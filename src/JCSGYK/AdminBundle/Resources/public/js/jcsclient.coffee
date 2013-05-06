###
    Sets up the client block actions
###
JcsClient =
    init: ->
        # init toggles
        JcsMenu.submenu()
        JcsToggle.init("clientblock")
        HBlocks.setCloseButtons()
        $("#clientblock").show()
        HBlocks.setBlockSizes()

        # if client block is already filled, we show and focus on it
        if $("#clientblock .clientcontent").text() != ""
            $("#clientblock .clientcontent").show()
            if $("#problemblock .problemcontent").text() == ""
                HBlocks.scrollTo(2)

        @initButtonRow()
        @initForm()
        @setDelProvider()
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
            @addProviderForm($(".utilityproviders"))

            false

        # client edit
        $("#client_edit").submit ->
            $(".save_client").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                $("#clientblock .clientcontent").html(data).show()
                # display the result message
                msg_container = $("#clientblock .clientcontent").find(".result")
                if $(msg_container).data("result-notice")
                    AjaxBag.showNotice($(msg_container).data("result-notice"))
                if $(msg_container).data("result-error")
                    AjaxBag.showError($(msg_container).data("result-error"))
                JcsClient.init()
            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $(".save_client").removeClass('animbutton')
            )

            false

        # textarea auto height
        $("#client_note").elastic()

    reloadProblems: (problemid) ->
        # save the cursor and current rows

        # get the url
        url = $("#clientblock .problem_container").data("url")
        if url
            $.get(url, (data) =>
                $("#clientblock .problem_container").html(data)
                JcsClient.initProblems()
                # restore the cursor and current classes
                $("#clientblock .problem_container tr").removeClass("current cursor")
                $("#clientblock .problem_container tr").each ->
                    if $(this).data("problemid") == problemid
                        $(this).addClass("cursor current")

            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
            )
        false

    initVisit: ->
        JcsModal.setCloseButton()
        # form submit
        $("#visit_form").submit ->
            # check for selected user
            if !$("[name='form[userlist]']:checked").length
                # no user selected
                AjaxBag.showError($("#visit-error").text())

                return false

            $(".save-visit").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                JcsModal.setContent(data)

                # display the result message
                if JcsModal.find(".result").data("result-notice")
                    AjaxBag.showNotice(JcsModal.find(".result").data("result-notice"))
                    JcsModal.close()
                    # refresh the client block
                else
                    JcsClient.initVisit()

            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $(".save-visit").removeClass('animbutton')
            )

            false

        JcsModal.load()

    initArchive: ->
        JcsModal.setCloseButton()

        # form submit
        $("#archive_form").submit ->
            $(".save_archive").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                JcsModal.setContent(data)

                # display the result message
                if JcsModal.find(".result").data("result-notice")
                    AjaxBag.showNotice(JcsModal.find(".result").data("result-notice"))
                    JcsModal.close()
                    # refresh the client block
                    client_url = $("#clientblock .clientcontent").data("url")
                    $.get(client_url, (data) ->
                        $("#clientblock .clientcontent").html(data).show()
                        HBlocks.scrollTo(2)
                        HBlocks.closeBlock(3)
                        JcsClient.init()
                    ).error( (data) ->
                        # there was some error :(
                        AjaxBag.showError(data.statusText)
                        HBlocks.closeBlock(2)
                    )
                else
                    JcsClient.initArchive()

            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $(".save_archive").removeClass('animbutton')
            )

            false

        # textarea auto height
        $("#archive_description").elastic()

        JcsModal.load()
        $("#archive_type").focus()

    setDelProvider: ->
        # provider delete
        $(".delete_provider").off("click").on "click", (event) ->
            $(this).parent().parent().hide().find("input").attr('value', '')

    addProviderForm: (collectionHolder) ->
        prototype = collectionHolder.data('prototype')
        index = collectionHolder.data('index')
        # Replace '__name__' in the prototype's HTML to
        # instead be a number based on how many items we have
        newForm = prototype.replace(/__name__/g, index)
        # increase the index with one for the next item
        collectionHolder.data('index', index + 1)
        collectionHolder.append(newForm)
        @setDelProvider()

    initButtonRow: ->
        # get buttons
        $(".edit_client").add(".back_to_view").add(".new_client").off('click').on 'click', (event) ->
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

        $(".new_client").on "click", (event) ->
            $("#clientblock .clientcontent").hide()
            HBlocks.closeBlock(4)
            HBlocks.closeBlock(3)
            false

        # archive
        $(".archive_client").add(".client_visit").on "click", (event) ->
            event.stopPropagation()

            if !$(this).hasClass('animbutton')
                $(this).addClass('animbutton')

                $.get($(this).attr("href"), (data) =>
                    $(this).removeClass('animbutton')
                    JcsModal.setContent(data)

                    if $(this).hasClass('client_visit')
                        JcsClient.initVisit()
                    if $(this).hasClass('archive_client')
                        JcsClient.initArchive()
                    # hide the submenu
                    if $(this).parent().hasClass('sub-vertical')
                        $(this).parent().hide()

                ).error( (data) =>
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $(this).removeClass('animbutton')
                )
            false

        # modal dialog
        JcsModal.init()

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

        # new client button
        JcsProblem.initButtonRow()
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
                problem_url = $("#getproblemform").attr("action") + "/" + $(this).data("problemid")
                $.get(problem_url, (data) ->
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