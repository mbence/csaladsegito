###
    Sets up the client block actions
###
JcsCatering =
    init: ->
        @initButtonRow()
        true

    reloadCatering: ->
        # get the url
        url = $("#clientblock .catering_container").data("url")
        if url
            $.get(url, (data) =>
                $("#clientblock .catering_container").html(data)
                JcsCatering.initButtonRow()
            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
            )
        false

    initCatering: ->
        JcsModal.setCloseButton()
        JcsCatering.initMultiDatesPicker()
        # day selectors
        $(".day-selectors > a").on "click", ->
            days = $(this).data("days")
            for day, index in days
                $("#catering_form input[name='catering[subscriptions][" + index + "]']").prop("checked", day)
            false
        # active - inactive radio
        $("input[name='catering[is_active]']").on "change", ->
            if "1" == $("input[name='catering[is_active]']:checked").val()
                $("#catering-fields").removeClass("disabled")
            else
                $("#catering-fields").addClass("disabled")

        # form submit
        $("#catering_form").submit ->
            $(".save-catering").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                JcsModal.setContent(data)

                # display the result message
                if JcsModal.find(".result").data("result-notice")
                    AjaxBag.showNotice(JcsModal.find(".result").data("result-notice"))
                    JcsModal.close()
                    # refresh the parent block
                    JcsCatering.reloadCatering()
                else
                    JcsCatering.initCatering()

            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $(".save-catering").removeClass('animbutton')
            )

            false

        # textarea auto height
        $("#archive_description").elastic()

        JcsModal.load()
        $("#archive_type").focus()

    initButtonRow: ->
        # archive
        $(".edit_catering")
                .add(".catering_orders")
                .add(".catering_invoices")
                .off('click').on "click", (event) ->

            event.stopPropagation()

            if !$(this).hasClass('animbutton')
                $(this).addClass('animbutton')

                $.get($(this).attr("href"), (data) =>
                    $(this).removeClass('animbutton')
                    JcsModal.setContent(data)

                    JcsCatering.initCatering()

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
    
    ###
        Init and setup ordering table
    ###
    initMultiDatesPicker: ->
        @calendarNavigation()
        @setupDatePickering()
        $(".month-wrapper:first").addClass("active")
        $(".title-date").text(" - " + $(".month-wrapper:first").data("date"))

    calendarNavigation: ->
        $(".calendar-nav li").click ->
            direction    = $(this).data("direction")
            next_element = $(this).data("next")
            # console.log(direction + ": " + next_element)
            if next_element != -1 and $(".month-wrapper:eq(" + next_element + ")").length
                $(".month-wrapper").removeClass("active")
                $(".month-wrapper:eq(" + next_element + ")").addClass("active")
                $(".title-date").text(" - " + $(".month-wrapper:eq(" + next_element + ")").data("date"))
                $(".calendar-nav li").each ->
                    if direction == "prev"
                        $(this).data().next--
                    else if direction == "next"
                        $(this).data().next++

    setupDatePickering: ->
        $("#ordering-calendar").on "click", "li.day", ->
            if $(this).hasClass("modifiable")
                $(this).toggleClass("selected")
                if $(this).find("input").get(0).checked
                    $(this).find("input").removeAttr("checked")
                else
                    $(this).find("input").attr("checked","checked")
