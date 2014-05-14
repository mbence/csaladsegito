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
        @setupDatePicker()
        $(".month-wrapper:eq(2)").addClass("active")
        $(".title-date").text(" - " + $(".month-wrapper:eq(2)").data("date"))
        $("li.day").each ->
            if $(this).data("order") == "order" || $(this).data("order") == "reorder"
                $(this).find("input").attr("checked","checked")
            if $(this).data("modifiable") == 0
                $(this).find("input").attr("disabled","disabled")

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

    setupDatePicker: ->
        $("#ordering-calendar").on "click", "li.day", ->
            if $(this).data("modifiable")
                order = $(this).data("order")
                new_order = $(this).data("new_order")

                if order == "cancel" && new_order == undefined
                    $(this).data("new_order", "reorder")
                    $(this).find(".menu").text($(this).data("menu"))
                    $(this).find(".status").text("Utánrendelve")
                    $(this).removeClass("cancel").addClass("reorder")
                    $(this).find("input").attr("checked","checked")
                else if order == "cancel" && new_order != undefined
                    $(this).removeData("new_order")
                    $(this).find(".menu").text("Lemondva")
                    $(this).find(".status").empty()
                    $(this).removeClass("reorder").addClass("cancel")
                    $(this).find("input").removeAttr("checked")

                if order == "none" && new_order == undefined
                    $(this).data("new_order", "reorder")
                    $(this).find(".status").text("Utánrendelve")
                    $(this).addClass("reorder")
                    $(this).find("input").attr("checked","checked")
                else if order == "none" && new_order != undefined
                    $(this).removeData("new_order")
                    $(this).find(".status").empty()
                    $(this).removeClass("reorder")
                    $(this).find("input").removeAttr("checked")

                if order == "order" && new_order == undefined
                    $(this).data("new_order", "cancel")
                    $(this).find(".menu").text("Lemondva")
                    $(this).removeClass("order").addClass("cancel")
                    $(this).find("input").removeAttr("checked")
                else if order == "order" && new_order != undefined
                    $(this).removeData("new_order")
                    $(this).find(".menu").text($(this).data("menu"))
                    $(this).removeClass("cancel").addClass("order")
                    $(this).find("input").attr("checked","checked")

                if order == "reorder" && new_order == undefined
                    $(this).data("new_order", "cancel")
                    $(this).find(".menu").text("Lemondva")
                    $(this).find(".status").empty()
                    $(this).removeClass("reorder").addClass("cancel")
                    $(this).find("input").removeAttr("checked")
                else if order == "reorder" && new_order != undefined
                    $(this).removeData("new_order")
                    $(this).find(".menu").text($(this).data("menu"))
                    $(this).find(".status").text("Utánrendelve")
                    $(this).removeClass("cancel").addClass("reorder")
                    $(this).find("input").attr("checked","checked")
        
        $("#ordering-calendar").on "click", "li.day input", (event) ->
            $(this).parents("li.day").trigger("click")
            event.stopPropagation()

