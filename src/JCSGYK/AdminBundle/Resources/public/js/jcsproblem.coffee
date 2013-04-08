###
    Sets up the Problem block actions
###
JcsProblem =
    init: ->
        JcsMenu.submenu()
        # init toggles
        JcsToggle.init("problemblock")
        HBlocks.setCloseButtons()
        @setupEvents()
        @initForm()
        @setDelDebt()
        @initButtonRow()
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
                event_url = $("#geteventform").attr("action") + "/" + $(this).data("eventid")
                $.get(event_url, (data) ->
                    $("#eventblock .loading").hide()
                    $("#eventblock .eventcontent").html(data).show()
                    $("#eventblock .eventcontent").data("url", event_url)
                    JcsEvent.init()
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
        # JcsEvent.init()

    initForm: ->
        # count the current debt records we have (e.g. 2), use that as the new
        # index when inserting a new item (e.g. 2)
        $(".debts").data('index', $(".debts").find('tr').length - 1);
        $(".add_debt").on 'click', (e) =>
            # prevent the link from creating a "#" on the URL
            e.preventDefault()

            # add a new tag form (see next code block)
            @addDebtForm($(".debts"))

            false

        # problem edit
        $("#problem_edit").submit ->
            $(".save_problem").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                $("#problemblock .problemcontent").html(data).show()
                # display the result message
                msg_container = $("#problemblock .problemcontent").find(".result")
                if $(msg_container).data("result-notice")
                    AjaxBag.showNotice($(msg_container).data("result-notice"))
                if $(msg_container).data("result-error")
                    AjaxBag.showError($(msg_container).data("result-error"))
                JcsProblem.init()
                JcsClient.reloadProblems($("#problemblock #problem-id").data("problemid"))
            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $(".save_problem").removeClass('animbutton')
            )

            false

        # textarea auto height
        $("#problem_description").elastic()

    addDebtForm: (collectionHolder) ->
        prototype = collectionHolder.data('prototype')
        index = collectionHolder.data('index')
        # Replace '__name__' in the prototype's HTML to
        # instead be a number based on how many items we have
        newForm = prototype.replace(/__name__/g, index)
        # increase the index with one for the next item
        collectionHolder.data('index', index + 1)
        collectionHolder.append(newForm)
        @setDelDebt()

    setDelDebt: ->
        # debt delete
        $(".delete_debt").off("click").on "click", (event) ->
            $(this).parent().parent().hide().find("input").each ->
                $(this).attr('value', '')

    initButtonRow: ->
        # get buttons
        $(".edit_problem").add(".back_to_problem").add(".new_problem").off('click').on 'click', (event) ->
            if !$(this).hasClass('animbutton')
                $(this).addClass('animbutton')
                HBlocks.scrollTo(3)

                $.get($(this).attr("href"), (data) =>
                    $(this).removeClass('animbutton')
                    $("#problemblock .problemcontent").html(data).show()
                    JcsProblem.init()
                ).error( (data) =>
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $(this).removeClass('animbutton')
                )
            false

        $(".new_problem").on 'click', (event) ->
            $("#problemblock .problemcontent").hide()
            $("#problemblock").show()
            HBlocks.setBlockSizes()
            HBlocks.closeBlock(4)
            false

        # close problem
        $(".close_problem").on "click", (event) ->
            if !$(this).hasClass('animbutton')
                $(this).addClass('animbutton')

                $.get($(this).attr("href"), (data) =>
                    $(this).removeClass('animbutton')
                    $(".modal .modal-content").html(data).show()
                    JcsProblem.initCloseProblem()
                    # hide the submenu
                    $(this).parent().hide()

                ).error( (data) =>
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $(this).removeClass('animbutton')
                )
            false

        # delete problem
        $(".delete_problem").on "click", (event) ->
            if !$(this).hasClass('animbutton')
                $(this).addClass('animbutton')

                $.get($(this).attr("href"), (data) =>
                    $(this).removeClass('animbutton')
                    $(".modal .modal-content").html(data).show()
                    JcsProblem.initDeleteProblem()
                    # hide the submenu
                    $(this).parent().hide()

                ).error( (data) =>
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $(this).removeClass('animbutton')
                )
            false

        # modal dialog
        $(".modal").overlay
            # some mask tweaks suitable for modal dialogs
            mask:
                color: '#ebecfe'
                loadSpeed: 0
                closeSpeed: 0
                opacity: 0.9
            closeOnClick: true
            left: "center"
            target: ".modal"
            load: false
            speed: 0
            closeSpeed: 0

    initCloseProblem: ->
        $(".modal .modal-content .close").off("click").on "click", ->
            $(".modal").overlay().close()

        # form submit
        $("#problem_close_form").submit ->
            $(".save_problem").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                $(".modal .modal-content").html(data).show()

                # display the result message
                if $(".modal .modal-content").find(".result").data("result-notice")
                    AjaxBag.showNotice($(".modal .modal-content").find(".result").data("result-notice"))
                    $(".modal").overlay().close()
                    # refresh the problem block
                    problem_url = $("#problemblock .problemcontent").data("url")
                    $.get(problem_url, (data) ->
                        $("#problemblock .problemcontent").html(data).show()
                        HBlocks.scrollTo(3)
                        JcsProblem.init()
                        JcsClient.reloadProblems($("#problemblock #problem-id").data("problemid"))
                        HBlocks.closeBlock(4)
                    ).error( (data) ->
                        # there was some error :(
                        AjaxBag.showError(data.statusText)
                        HBlocks.closeBlock(3)
                    )
                else
                    JcsProblem.initCloseProblem()

            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $(".save_problem").removeClass('animbutton')
            )

            false

        $(".modal").overlay().load()
        $("#problem_close_code").focus()

    initDeleteProblem: ->
        $(".modal .modal-content .close").off("click").on "click", ->
            $(".modal").overlay().close()

        # form submit
        $("#problem_delete_form").submit ->
            $(".delete_problem").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                $(".modal .modal-content").html(data).show()

                # display the result message
                if $(".modal .modal-content").find(".result").data("result-notice")
                    AjaxBag.showNotice($(".modal .modal-content").find(".result").data("result-notice"))
                    $(".modal").overlay().close()
                    JcsClient.reloadProblems($("#problemblock #problem-id").data("problemid"))
                    HBlocks.closeBlock(3)
                else
                    JcsProblem.initDeleteProblem()

                $(".delete_problem").removeClass('animbutton')

            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $(".delete_problem").removeClass('animbutton')
            )

            false

        $(".modal").overlay().load()

    reloadEvents: (eventid) ->
        # get the url
        url = $("#problemblock .event_container").data("url")
        if url
            $.get(url, (data) =>
                $("#problemblock .event_container").html(data)
                JcsProblem.setupEvents()

                # restore the cursor and current classes
                $("#problemblock .event_container tr").removeClass("current cursor")
                $("#problemblock .event_container tr").each ->
                    if $(this).data("eventid") == eventid
                        $(this).addClass("cursor current")

            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
            )
        false