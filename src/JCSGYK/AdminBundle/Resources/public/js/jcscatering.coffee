###
    Sets up the client block actions
###
JcsCatering =
    init: ->
        @initButtonRow()
        @initManualInvoice()
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
        @initMultiDatesPicker()
        @initInvoices()
        @initMenuList()
        @initHomeHelp()

        # club selector
        $("#catering_club").on "change", =>
            @initHomeHelp()

        # discount datepicker
        $('.inblock .datepicker').each ->
            $(this).datepicker({dateFormat: 'yy-mm-dd'})

        # clear paused dates
        $('.clear-paused-dates').on('click', ->
            $('#catering_pausedFrom').add('#catering_pausedTo').val('')
        )

        # day selectors
        $(".day-selectors > a").on "click", ->
            days = $(this).data("days")
            for day, index in days
                $("#catering_form input[name='catering[subscriptions][" + index + "]']").prop("checked", day)
            false
        # active - inactive radio
        $("input[name='catering[is_active]']").on "change", ->
            $("#catering_form .warning").hide()
            if "1" == $("input[name='catering[is_active]']:checked").val()
                $("#catering-fields").removeClass("disabled")
                $("#catering_form .is_active_reorder").show()
            else
                $("#catering-fields").addClass("disabled")
                $("#catering_form .is_active_cancel").show()

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
                    JcsClient.reloadClient()
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

    initInvoices: ->
        # always clear the cancel_id
        $("#form_cancel_id").val("")

        $("button.invoice_full_amount").on "click", ->
            amount = $(this).data("amount")
            $('input[type=text]', $(this).parent()).val(amount)
        # focus the first input field
        $("#catering_form input[type=text]").first().focus()
        # click action on the tr-s
        $(".catering-invoice").on "click", ->
            id = $(this).data("id")
            $(".i" + id + "_payments").toggle()
        # close the payed (closed) invoice details
        $(".catering-invoice").each ->
            if 3 == $(this).data("status")
                $(this).click()
        $(".invoice_cancel").on "click", ->
            if confirm("Biztos benne?")
                id = $(this).data("id")
                $("#form_cancel_id").val(id)
                $("#catering_form").submit()


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

    initHomeHelp: ->
        if clubs_type_list?
            selected_club = $("#catering_club").val()
            if (clubs_type_list[selected_club]?)
                if 0 == clubs_type_list[selected_club]
                    # show all fields for homehelp type
                    $("#catering_form .help-type").show()
                else
                    # hide unnecessary fileds for visit type (clubs)
                    $("#catering_form .help-type").hide()


    initMenuList: ->
        if $("#catering_menu").length and $("#catering_club").length
            # save selected menu for later using
            $("#catering_menu").data("selected_menu", $("#catering_menu").val())
            JcsCatering.processMenuList($("#catering_club").val())
            JcsCatering.setupMenuFiltering()

    setupMenuFiltering: ->
        $("#catering_club").change ->
            JcsCatering.processMenuList($(this).val())

    processMenuList: (club_id) ->
        # remove all options
        $("#catering_menu").empty()
        # build the new menu list based on club_id
        for menu_id,menu_name of clubs_menu_list[club_id]
            $("#catering_menu").append("<option value=\"" + menu_id + "\">" + menu_name + "</option>")
        # if saved menu exists, set it selected
        if $("#catering_menu option[value=" + $("#catering_menu").data("selected_menu") + "]").length
            $("#catering_menu").val($("#catering_menu").data("selected_menu"))


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
        $(".title-date").text($(".month-wrapper:eq(2)").data("date"))

    prepareCalendar: ->
        $("li.day").each ->
            if $(this).data("order") == "order" || $(this).data("order") == "reorder"
                $(this).find("input").attr("checked","checked")
            # else if $(this).data("order") == "cancel"
            #     $(this).find("input.cancel").attr("checked","checked")
            if $(this).data("modifiable") == 0
                $(this).find("input").attr("disabled","disabled")

    prepareOrders: ->
        orders = {}
        $("li.day.modifiable").each ->
            if $(this).data("order") == "order"
                orders[$(this).data("date")] = 1
            else if $(this).data("order") == "cancel"
                orders[$(this).data("date")] = -1
        $("input[name=orders]").val(JSON.stringify(orders))

    processOrders: ->
        orders = {}
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

    setupHolidayInfo: ->
        $("span.holiday").mouseenter ->
            $(this).find(".desc").fadeIn(200)
        $("span.holiday").mouseleave ->
            $(this).find(".desc").fadeOut(200)

    calendarNavigation: ->
        if  $("#ordering-calendar").length
            $("#ordering-calendar").scrollable({
                'initialIndex': 2,
                'onSeek' : (event) ->
                    $(".title-date").text($(".month-wrapper:eq(" + this.getIndex() + ")").data("date"))
            })
            setTimeout( ->
                $("#ordering-calendar").data("scrollable").seekTo(2, 0)
            , 200)

    setupDatePicker: ->
        $("#ordering-calendar").on "click", "li.day", ->
            if $(this).data("modifiable")
                order = $(this).data("order")
                new_order = $(this).data("new_order")

                if order == "cancel" && new_order == undefined
                    $(this).data("new_order", "reorder")
                    $(this).find(".menu .order-menu").show().prop("disabled", false)
                    $(this).find(".menu .menu-text").hide()
                    # if it is closed, this is a reorder
                    if $(this).data("closed") == 1
                        $(this).find(".status").text("Utánrendelve")
                        $(this).removeClass("cancel").addClass("order")
                    else if $(this).data("closed") == 0
                        $(this).removeClass("cancel").addClass("reorder")
                    $(this).find("input").attr("checked","checked")
                else if order == "cancel" && new_order != undefined
                    $(this).removeData("new_order")
                    $(this).find(".menu .order-menu").hide().prop("disabled", true)
                    $(this).find(".menu .menu-text").show()
                    $(this).find(".status").empty()
                    $(this).removeClass("reorder").addClass("cancel")
                    $(this).find("input").removeAttr("checked")

                # ha még nincs order az adott napon
                if order == "none" && new_order == undefined
                    $(this).data("new_order", "reorder")
                    $(this).find(".menu .order-menu").show().prop("disabled", false)
                    $(this).find(".menu .menu-text").hide()
                    $(this).addClass("reorder")
                    $(this).find("input").attr("checked","checked")
                else if order == "none" && new_order is "reorder"
                    $(this).data("new_order", "cancel")
                    $(this).find(".status").empty()
                    $(this).find(".menu .order-menu").hide().prop("disabled", true)
                    $(this).find(".menu .menu-text").show()
                    $(this).removeClass("reorder").addClass("cancel")
                    $(this).find("input").removeAttr("checked")
                else if order == "none" && new_order is "cancel"
                    $(this).removeData("new_order")
                    $(this).find(".status").empty()
                    $(this).find(".menu .order-menu").hide().prop("disabled", true)
                    $(this).find(".menu .menu-text").hide()
                    $(this).removeClass("cancel")
                    # $(this).find("input").removeAttr("checked")

                if order is "order" and new_order is undefined
                    $(this).data("new_order", "cancel")
                    # if $(this).data("closed") == 1
                    $(this).find(".menu .order-menu").hide().prop("disabled", true)
                    $(this).find(".menu .menu-text").show()
                    $(this).removeClass("order").addClass("cancel")
                    # else $(this).data("closed") == 0
                        # $(this).removeClass("order").addClass("cancel")
                    $(this).find("input").removeAttr("checked")
                else if order == "order" && new_order != undefined
                    $(this).removeData("new_order")
                    $(this).find(".menu .order-menu").show().prop("disabled", false)
                    $(this).find(".menu .menu-text").hide()
                    $(this).removeClass("cancel").addClass("order")
                    $(this).find("input").attr("checked","checked")

                if order == "reorder" && new_order == undefined
                    $(this).data("new_order", "cancel")
                    $(this).find(".menu .order-menu").hide().prop("disabled", true)
                    $(this).find(".menu .menu-text").show()
                    $(this).find(".status").empty()
                    $(this).removeClass("reorder").addClass("cancel")
                    $(this).find("input").removeAttr("checked")
                else if order == "reorder" && new_order != undefined
                    $(this).removeData("new_order")
                    $(this).find(".menu .order-menu").show().prop("disabled", false)
                    $(this).find(".menu .menu-text").hide()
                    if $(this).data("closed") == 1
                        $(this).find(".status").text("Utánrendelve")
                    $(this).removeClass("cancel").addClass("reorder")
                    $(this).find("input").attr("checked","checked")

                JcsCatering.processOrders()

        $("#ordering-calendar").on "click", "li.day input[type=checkbox]", (event) ->
            $(this).parents("li.day").trigger("click")
            event.stopPropagation()

        $("#ordering-calendar select.order-menu").on("click", (event) ->
            event.stopPropagation()
        )

    ###
      Manual invoice button
    ###
    initManualInvoice: ->
        $("#create-invoice").off("click").on("click", ->
            if confirm("Biztos benne?")
                $form = $("#create-invoice-form")
                $.post($form.attr("action"), $form.serialize(), (data) ->
                    if data and data != "0"
                        AjaxBag.showNotice("A számla kiállítása sikeres")
                        $(".catering_container a.catering_invoices").click()
                        JcsClient.reloadClient()
                    else
                        AjaxBag.showError("Hiba a számla kiállítása során, kérem próbálja újra!")
                )
        )
