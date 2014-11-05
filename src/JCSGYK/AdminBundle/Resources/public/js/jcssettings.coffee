JcsSettings =
    hhSumCol: 0,
    hhSumRows: [],
    hhLastRow: 0,
    hhWeekends: [],

    init: ->
        # users
        if $("#userlist").length
            @setupUsers()
            @setupUserFilter()

            @setupForm()
            @setHeights()
            $(window).resize =>
                @setHeights()

        # params
        if $(".parameter-groups").length
            @setupParams()

        # tempaltes
        if $("#template-edit").length
            @setupTemplates()

        # companies
        if $("#company_types").length
            @setupCompanies()

        # table editor
        if $("#handsontable").length
            @initTableEditor()

        # Recommended Fields
        if $("#recommended_fields").length
            @setupRecFields()

        # home help
        if $("#homehelpfilter").length
            @setupHomehelp()

        # club visit
        if $("#clubvisitfilter").length
            @setupClubvisit()

    ###
        setup the homehelp admin
    ###
    setupHomehelp: ->
        $("#form_social_worker").add("#form_month").on("change", ->
            $("#homehelpfilter").submit()
        )
        $("#homehelpfilter").submit ->
            url = $(this).attr('action') + '/' + $("#form_social_worker").val() + '/' + $("#form_month").val()
            document.location = url

            return false

        # init the add client button
        $(".add-client-dialog").off('click').on "click", (event) ->
            event.stopPropagation()

            if !$(this).hasClass('animbutton')
                $(this).addClass('animbutton')

                url = $(this).data("href") + '/' + $("#form_social_worker").val() + '/' + $("#form_month").val()

                $.get(url, (data) =>
                    $(this).removeClass('animbutton')
                    JcsModal.setContent(data)
                    JcsSettings.initAddclientDialog()
                ).error( (data) =>
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $(this).removeClass('animbutton')
                )
            return false

        # form cancel button
        $("#homehelpform button.cancel").on "click", ->
              document.location.reload()

        # modal dialog
        JcsModal.init()

    ###
        setup the club visit admin
    ###
    setupClubvisit: ->
        $("#form_club").add("#form_date").on("change", ->
            $("#clubvisitfilter").submit()
        )
        $("#clubvisitfilter").submit ->
            url = $(this).attr('action') + '/' + $("#form_club").val() + '/' + $("#form_date").val()
            document.location = url

            return false

        # form cancel button
        $("#homehelpform button.cancel").on "click", ->
            document.location.reload()
        # date back/next buttons
        $("#form_back").add("#form_forward").on "click", ->
            $(this).addClass('animbutton')
            $("#form_date").val($(this).val())
            $("#clubvisitfilter").submit()

        $('#clubvisitfilter .datepicker').datepicker({ dateFormat: 'yy-mm-dd' })

    initAddclientDialog: ->
        JcsModal.setCloseButton()
        # filter input
        filter_timeout = null
        $("#form_filter").on "input", ->
            clearTimeout(filter_timeout)
            filter_timeout = setTimeout( ->
                JcsSettings.filterClients($("#form_filter").val())
            , 200)

        # form submit
        $addClientForm = $("#addclient_form");

        $addClientForm.submit ->
            # check for selected user
            if !$("[name='form[clients][]']:checked").length
                # no client selected
                AjaxBag.showError($("#noclient-error").text())

                return false

            $("button.add-client").addClass('animbutton')
            $.post($(this).attr("action"), $(this).serialize(), (data) ->
                JcsModal.setContent(data)

                # handle the final operations
                resdiv = JcsModal.find("#addclient_results")
                if resdiv.length
                    $("#form_to_add").val(JSON.stringify(resdiv.data("to-add")))
                    $("#form_to_remove").val(JSON.stringify(resdiv.data("to-remove")))
                    $("#homehelpform").submit()
                else
                    JcsSettings.initAddclientDialog()

            ).error( (data) =>
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $("button.add-client").removeClass('animbutton')
            )

            return false

        # set my clients checkboxes to disabled
        if $("#form_my_clients").val()
            my_cl = JSON.parse($("#form_my_clients").val())
            for cl_id in my_cl when $addClientForm.find("input[value=" + cl_id + "]").is(":checked")
                $addClientForm.find("input[value=" + cl_id + "]").attr("disabled", true)

        JcsModal.load()

    filterClients: (input) ->
        $(".client-template-list > div > label").each ->
            if input and
                    -1 == $(this).html().toLocaleLowerCase().indexOf(input.toLocaleLowerCase()) and
                    not $(this).prev().is(":checked") # dont hide checked rows
                $(this).parent().hide()
            else
                $(this).parent().show()


    ###
        setup the company editor
    ###
    setupCompanies: ->
        # client types
        $("#company_types input").each ->
            check = $(this)
            tr = $('#co-admin-type-' + check.val())
            if !check.is(':checked')
                tr.addClass('inactive')
            else
                tr.removeClass('inactive')
            check.off('click').on('click',  ->
                JcsSettings.setupCompanies()
            )

    ###
        setup the template editor
    ###
    setupTemplates: ->
        # cancel button
        $("#template-edit .cancel").click ->
            document.location = $(this).attr('href')

    ###
        setup the parameter editor
    ###
    setupParams: ->
        # find the initial tab
        $(".admin-ct-tabs").tabs(".admin-panes > div", {
          "initialIndex": null
        });
        if $(".parameter-groups ul li.current").length
            ct = $(".parameter-groups ul li.current").closest(".admin-pane").data("ctid")
        else
            ct = $(".admin-pane").first().data("ctid")
        $("#admin-ct-tab-" + ct).click()

        $(".paramlist").hide()
        # group selector
        $(".parameter-groups li").click (event) ->
            grp = $(this).data("groupid")
            if grp?
                event.stopPropagation()
                $("#paramedit").show()
                $("#paramedit .loading").show()
                $("#paramedit .usercontent").hide()
                $(".paramlist").hide()
                $("#paramlist-" + grp).show();

                $(".parameter-groups li").removeClass("current cursor")
                $(this).addClass("current cursor")
        # add new parameter
        $(".new-param").click ->
            pos = $(this).parent().prev().children(".param").length + 1
            group = $(this).parent().parent().children("[name=group]").val()
            ct = $(this).parent().parent().children("[name=clientType]").val()
            $(this).parent().prev().append($("#newparam-template").html().replace(/%pos%/g, pos).replace('%grp%', group).replace('%ct%', ct))
            $(this).parent().prev().children().last().children(":text").focus()
            $( ".paramcontainer" ).sortable( "refresh" );

        # cancel button
        $(".parameter-lists .paramform .cancel").click ->
            $(".paramcontainer .newparam").remove()
            $( ".paramcontainer" ).sortable("cancel");

        # form submit animation
        $(".parameter-lists .paramform").submit ->
            if !$(".paramsave", this).hasClass('animbutton')
                $(".paramsave", this).addClass('animbutton')
            else
                return false
        # sortable parameters
        $( ".paramcontainer" ).sortable({
            axis: "y"
            cursor: "move"
            containment: "parent"
            handle: ".pos"
            helper: "clone"
            items: ".param"
            opacity: 0.7
            tolerance: "pointer"
        }).on "sortupdate", (event, ui) ->
            $(ui.item).parent().children(".param").each (index) ->
                $(".hiddenpos", this).val(index + 1)

        $(".parameter-lists").show()
        $(".parameter-groups li.current").click()

    ###
        setup the user editor
    ###
    setupUsers: ->
        $("#userlist tbody tr").click( (event) ->
            if $(this).data("user-edit")
                event.stopPropagation()
                $("#userlist tr").removeClass("current cursor")
                $(this).addClass("current cursor")
                document.location = $(this).data("user-edit")
        )

    setupUserFilter: ->
        $("#userfilter input")
            .add $("#userfilter select")
            .on "change", ->
                $("#userfilter").submit()

    setHeights: ->
        h = $(window).innerHeight() - $('#header').outerHeight() - $('#colophon').outerHeight() - 36
        # set heights
        $('#userlist').height(h)
        $('#useredit').height(h)
        true

    setupForm: ->
        $("#useredit .formbuttons .cancel").click ->
            $("#useredit .usercontent").html("")
            $("#userlist tr").removeClass("current cursor")
            document.location = $(this).data('url')

        $("#useredit #editform").submit ->
            if !$("#useredit .formbuttons .usersave").hasClass('animbutton')
                $("#useredit .formbuttons .usersave").addClass('animbutton')

    ###
        Register "hu" language for Handsontable editor
    ###
    registerLanguage: ->
        numeral.language('hu', {
            delimiters: {
                thousands: ' ',
                decimal: ','
            },
            abbreviations: {
                thousand: 'e',
                million: 'm',
                billion: 'b',
                trillion: 't'
            },
            currency: {
                symbol: 'Ft'
            }
        })

    ###
        Handsontable default settings
    ###
    tableDefaults: {
        data: [],
        colHeaders: [],
        minSpareRows: 1,
        currentRowClassName: "currentRow",
        currentColClassName: "currentCol",
        columns: [],
        afterChange: ->
    }

    ###
        Init Handsontable jQuery based table editor
    ###
    initTableEditor: ->
        # load language settings
        @registerLanguage()

        if hh_weekends?
            @hhWeekends = JSON.parse(hh_weekends)

        data_field = if $("#options_value").length then "#options_value" else "#form_value"

        tableData = if $(data_field).val() then JSON.parse($(data_field).val()) else {}

        if tableData.length > 0
            # merge the options
            options = $.extend(true,{},@tableDefaults,tableDefaultOptions)

            # before change event only for home help
            options.beforeChange = (changes, source) ->
                if changes != null and options.sums? and options.sums
                    changes = JcsSettings.decFix(changes, source)
                    if $.isNumeric(changes[0][3]) and changes[0][3] < 0
                        return false
                    JcsSettings.sums(changes, source)

            # after change event
            options.afterChange = (changes, source) ->
                if changes != null
                    $(data_field).val(JSON.stringify(tableData))

            options.data = tableData

            # use the cells formatting function?
            if options.cells? and options.cells
                    options.cells = @cells

            # fire up the table!
            $("#handsontable").handsontable(options)

        # discount datepicker
        $('.datepicker').datepicker({ dateFormat: 'yy-mm-dd' })

    setupRecFields: ->
        # find the initial tab
        $(".admin-ct-tabs").tabs(".admin-panes > div", {
            "initialIndex": parseInt($("#form_act_tab").val()),
            "onClick": ->
                $("#form_act_tab").val(this.getIndex())
        });

    cells: (row, col, prop) ->
        cellProperties = {}
        table_data = $("#handsontable").handsontable('getData')
        sum_col = $("#handsontable").handsontable('countCols') - 2

        if table_data[row] == null
            cellProperties.readOnly = true
            cellProperties.className = "hh-separator"
        else if table_data[row][0] == 'sum' or col >= sum_col
            cellProperties.readOnly = true
            cellProperties.className = "hh-sum"
        if (col + 1) in JcsSettings.hhWeekends
            cellProperties.className = " hh-weekend"

        return cellProperties

    ###
        Changes decimals from , to .
    ###
    decFix: (changes, source) ->
        changes[0][3] = changes[0][3].replace /\,/, '.'

        return changes

    sums: (changes, source) ->
        # hello
        table_data = $("#handsontable").handsontable('getData')
        # get the last sum col
        @getColNums(table_data)
        for change in changes
            # expand the change array
            [row, col, from, to] = change

            if from
                @calcSums(table_data, row, col, (-1 * from))
            if to
                @calcSums(table_data, row, col, to)

    # find the sum col and row numbers
    getColNums: (table_data) ->
        if !@hhSumCol
            @hhSumCol = $("#handsontable").handsontable('countCols') - 1

        # get the sum rows (first is sum, second is the total sum)
        if !@hhSumRows.length
            for k, v of table_data
                if v? and v[0] == 'sum'
                  @hhSumRows.push (k * 1)
            @hhLastRow = k * 1

    ###
        Calculate the value of the summary cells
    ###
    calcSums: (table_data, row, col, value) ->
        visit = 0
        # fix the value and visits
        if $.isNumeric(value)
            value = parseFloat(value)
            if value
                visit = if value > 0 then 1 else -1
        else
            visit = if value then 1 else -1
            value = 0

        # all rows - add to the sum col
        @addToCell(table_data, row, @hhSumCol, value)

        # first block
        if row < @hhSumRows[0]
            @addToCell(table_data, @hhSumRows[0], col, value)
            # add to the total cell
            @addToCell(table_data, @hhSumRows[0], @hhSumCol, value)
            # visits col
            @addToCell(table_data, row, @hhSumCol + 1, visit)
            @addToCell(table_data, @hhSumRows[0], @hhSumCol + 1, visit)

        # second block
        if row < @hhSumRows[1]
            # we must add the value to the totals
            @addToCell(table_data, @hhSumRows[1], col, value)
            # add to the total cell
            @addToCell(table_data, @hhSumRows[1], @hhSumCol, value)

    ###
        Reset a cell to a numeric value, and add it
    ###
    addToCell: (table_data, row, col, value) ->
        if !$.isNumeric(table_data[row][col])
            table_data[row][col] = 0
        table_data[row][col] = parseFloat(table_data[row][col]) + value

        # remove 0
        table_data[row][col] = "" if !table_data[row][col]
