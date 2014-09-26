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

    ###
        setup the homehelp admin
    ###
    setupHomehelp: ->
        $("#homehelpfilter").submit ->
            url = $(this).attr('action') + '/' + $("#form_social_worker").val() + '/' + $("#form_month").val()
            document.location = url

            return false

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
        # merge the options
        options = $.extend(true,{},@tableDefaults,tableDefaultOptions)
        # after change event
        afterChange = (changes, source) ->
            if changes != null
                if options.sums? and options.sums
                    JcsSettings.decFix(changes, source)
                    JcsSettings.sums(changes, source)
                $(data_field).val(JSON.stringify(tableData))

        options.data = tableData
        options.afterChange = afterChange

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
        if table_data[row] == null
            cellProperties.readOnly = true
            cellProperties.className = "hh-separator"
        else if table_data[row][0] == 'sum' or !table_data[row][col + 2]?
            cellProperties.readOnly = true
            cellProperties.className = "hh-sum"
        else if (col + 1) in JcsSettings.hhWeekends
            cellProperties.className = "hh-weekend"

        return cellProperties

    ###
        Changes decimals from , to .
    ###
    decFix: (changes, source) ->
        $("#handsontable").handsontable('getData')[changes[0][0]][changes[0][1]] = changes[0][3].replace /\,/, '.'

    sums: (changes, source) ->
        # hello
        table_data = $("#handsontable").handsontable('getData')
        # get the last sum col
        if !@hhSumCol
          if table_data[0][0]?
              for k, v of table_data[0]
                  if !table_data[0][k + 1]?
                      @hhSumCol = k
        # get the sum rows
        if !@hhSumRows.length
            for k, v of table_data
                if v? and v[0] == 'sum'
                    @hhSumRows.push (k * 1)
            @hhLastRow = k * 1

        # only update the right section
        section_start = 0
        for k, v of @hhSumRows
            if section_start <= changes[0][0] < v
                start_row = section_start
                end_row = v - 1
                sum_row = v
                break
            else
                section_start = v + 2
        # change is in the last section
        if !start_row?
            start_row = section_start
            end_row = @hhLastRow

        # reset the sum row
        if sum_row? and table_data[sum_row]?
            for k, v of table_data[sum_row]
                table_data[sum_row][k] = 0

        # do the sums
        for r in [start_row .. end_row] by 1
            # reset the sum col
            table_data[r][@hhSumCol] = 0
            # loop over the row
            for k, v of table_data[r]
                k = k * 1
                # skip the first col, and check if we have a value
                if (0 < k) and v
                    # make sure v is a number
                    v = v * 1
                    # sum col
                    if @hhSumCol > k
                        table_data[r][@hhSumCol] += v
                    # sum row
                    if sum_row?
                        if !table_data[sum_row][k]?
                            table_data[sum_row][k] = 0
                        table_data[sum_row][k] += v

        $("#handsontable").handsontable('render')