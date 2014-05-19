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
        @setupHolidayInfo()
        @prepareCalendar()
        @prepareOrders()
        $(".month-wrapper:eq(2)").addClass("active")
        $(".title-date").text(" - " + $(".month-wrapper:eq(2)").data("date"))

    prepareCalendar: ->
        $("li.day").each ->
            if $(this).data("order") == "order" || $(this).data("order") == "reorder"
                $(this).find("input").attr("checked","checked")
            # else if $(this).data("order") == "cancel"
            #     $(this).find("input.cancel").attr("checked","checked")
            if $(this).data("modifiable") == 0
                $(this).find("input").attr("disabled","disabled")

    prepareOrders: ->
        orders = []
        $("li.day.modifiable").each ->
            if $(this).data("order") == "order"
                orders[$(this).data("date")] = 1
            else if $(this).data("order") == "cancel"
                orders[$(this).data("date")] = -1
        $("input[name=orders]").val(JSON.stringify(orders))

    processOrders: ->
        orders = []
        $("li.day.modifiable").each ->
            if $(this).data("new_order") isnt undefined and $(this).data("new_order") is "reorder"
                orders[$(this).data("date")] = 1
            else if $(this).data("new_order") isnt undefined and $(this).data("new_order") is "cancel"
                orders[$(this).data("date")] = -1
            else if $(this).data("order") is "order"
                orders[$(this).data("date")] = 1
            else if $(this).data("order") is "cancel"
                orders[$(this).data("date")] = -1
        $("input[name=orders]").val(JSON.stringify(orders))
        console.log($("input[name=orders]").val())

    setupHolidayInfo: ->
        $("span.holiday").mouseenter ->
            $(this).find(".desc").fadeIn(200)
        $("span.holiday").mouseleave ->
            $(this).find(".desc").fadeOut(200)

    calendarNavigation: ->
        $(".calendar-nav li").click ->
            direction    = $(this).data("direction")
            next_element = $(this).data("next")
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
                # console.log(new_order)

                if order == "cancel" && new_order == undefined
                    $(this).data("new_order", "reorder")
                    $(this).find(".menu").text($(this).data("menu"))
                    # if it is closed, this is a reorder
                    if $(this).data("closed") == 1
                        $(this).find(".status").text("Utánrendelve")
                        $(this).removeClass("cancel").addClass("order")
                    else if $(this).data("closed") == 0
                        $(this).removeClass("cancel").addClass("reorder")
                    $(this).find("input").attr("checked","checked")
                else if order == "cancel" && new_order != undefined
                    $(this).removeData("new_order")
                    $(this).find(".menu").text("Lemondva")
                    $(this).find(".status").empty()
                    $(this).removeClass("reorder").addClass("cancel")
                    $(this).find("input").removeAttr("checked")

                # ha még nincs order az adott napon
                if order == "none" && new_order == undefined
                    $(this).data("new_order", "reorder")
                    $(this).find(".menu").text($(this).data("menu"))
                    # if $(this).data("closed") == 1
                        # $(this).find(".status").text("Utánrendelve")
                    $(this).addClass("reorder")
                    $(this).find("input").attr("checked","checked")
                else if order == "none" && new_order is "reorder"
                    $(this).data("new_order", "cancel")
                    $(this).find(".status").empty()
                    $(this).find(".menu").text("Lemondás")
                    $(this).removeClass("reorder").addClass("cancel")
                    $(this).find("input").removeAttr("checked")
                else if order == "none" && new_order is "cancel"
                    $(this).removeData("new_order")
                    $(this).find(".status").empty()
                    $(this).find(".menu").empty()
                    $(this).removeClass("cancel")
                    # $(this).find("input").removeAttr("checked")

                if order is "order" and new_order is undefined
                    $(this).data("new_order", "cancel")
                    # if $(this).data("closed") == 1
                    $(this).find(".menu").text("Lemondva")
                    $(this).removeClass("order").addClass("cancel")
                    # else $(this).data("closed") == 0
                        # $(this).removeClass("order").addClass("cancel")
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
                    if $(this).data("closed") == 1
                        $(this).find(".status").text("Utánrendelve")
                    $(this).removeClass("cancel").addClass("reorder")
                    $(this).find("input").attr("checked","checked")

                JcsCatering.processOrders()
        
        $("#ordering-calendar").on "click", "li.day input[type=checkbox]", (event) ->
            $(this).parents("li.day").trigger("click")
            event.stopPropagation()

