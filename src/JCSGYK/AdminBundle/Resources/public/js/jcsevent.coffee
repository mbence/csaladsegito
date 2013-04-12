###
    Sets up the Event block actions
###
JcsEvent =
    init: ->
        JcsMenu.submenu()
        # init toggles
        JcsToggle.init("eventblock")
        HBlocks.scrollTo(4)
        HBlocks.setCloseButtons()

        @initForm()
        @initButtonRow()

        true

    initForm: ->
        # event edit
        $("#event_edit").submit ->
            $(".save_event").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                $("#eventblock .eventcontent").html(data).show()
                # display the result message
                msg_container = $("#eventblock .eventcontent").find(".result")
                if $(msg_container).data("result-notice")
                    AjaxBag.showNotice($(msg_container).data("result-notice"))
                if $(msg_container).data("result-error")
                    AjaxBag.showError($(msg_container).data("result-error"))
                JcsEvent.init()
                JcsProblem.reloadEvents($("#eventblock #event-id").data("eventid"))
            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $(".save_event").removeClass('animbutton')
            )

            false

        # textarea auto height
        $("#event_description").elastic()

    initButtonRow: ->
        # get buttons
        $(".edit_event").add(".back_to_event").add(".new_event").off('click').on 'click', (event) ->
            event.stopPropagation()
            if !$(this).hasClass('animbutton')
                $(this).addClass('animbutton')
                HBlocks.scrollTo(4)

                $.get($(this).attr("href"), (data) =>
                    $(this).removeClass('animbutton')
                    $("#eventblock .eventcontent").html(data).show()
                    JcsEvent.init()
                ).error( (data) =>
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $(this).removeClass('animbutton')
                )
            false

        $(".new_event").on 'click', (event) ->
            $("#eventblock .eventcontent").hide()
            $("#eventblock").show()
            HBlocks.setBlockSizes()
            false

        # delete event
        $(".delete_event").on "click", (event) ->
            if !$(this).hasClass('animbutton')
                $(this).addClass('animbutton')

                $.get($(this).attr("href"), (data) =>
                    $(this).removeClass('animbutton')
                    JcsModal.setContent(data)
                    JcsEvent.initDeleteEvent()
                    # hide the submenu
                    $(this).parent().hide()

                ).error( (data) =>
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $(this).removeClass('animbutton')
                )
            false

         # modal dialog
        JcsModal.init()


    initDeleteEvent: ->
        JcsModal.setCloseButton()

        # form submit
        $("#event_delete_form").submit ->
            $(".delete_event").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                JcsModal.setContent(data)

                # display the result message
                if JcsModal.find(".result").data("result-notice")
                    AjaxBag.showNotice(JcsModal.find(".result").data("result-notice"))
                    JcsModal.close()
                    JcsProblem.reloadEvents($("#eventblock #event-id").data("eventid"))
                    HBlocks.closeBlock(4)
                else
                    JcsEvent.initDeleteEvent()

                $(".delete_event").removeClass('animbutton')

            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $(".delete_event").removeClass('animbutton')
            )

            false

        JcsModal.load()
