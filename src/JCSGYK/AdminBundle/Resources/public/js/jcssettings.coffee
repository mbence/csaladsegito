JcsSettings =
    init: ->
        # users
        if $("#userlist").length
            @setupUsers()
            $("#userlist table").tablesorter()
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
        if $('#company_types').length
            @setupCompanies()

        # table editor
        if $('#catering-costs-table').length
            @initTableEditor()

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
        Init Handsontable jQuery based table editor
    ###
    initTableEditor: ->
        numericValidator: (value, callback) ->
            setTimeout( ->
                if /^[0-9]{1,10}$/.test(value)
                    callback(true)
                else
                    callback(false)
            , 1000)
            
        # load a language
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
        tableData = JSON.parse($("#options_value").val())
        $("#catering-costs-table").handsontable({
            data: tableData,
            colHeaders: ['-tól', '-ig', 'díj', 'egyedülálló'],
            minSpareRows: 1,
            currentRowClassName: 'currentRow',
            currentColClassName: 'currentCol',
            columns: [
                {
                    type: "numeric",
                    format: '0 0[,]00 $',
                    language: 'hu'
                    validator: @numericValidator,
                    allowInvalid: false
                },
                {
                    type: "numeric",
                    format: '0 0[,]00 $',
                    language: 'hu'
                },
                {
                    type: "numeric",
                    format: '0 0[,]00 $',
                    language: 'hu'
                },
                {
                    type: "checkbox"
                }
            ],
            afterChange: (changes, source) ->
                if changes != null
                    for row in tableData
                        irow = tableData.indexOf(row)
                        empty = true
                        for col in row
                            if empty
                                empty = (col==null) ? true : false
                            # console.log(i.indexOf(null))
                        if empty
                            strippedData = tableData
                            strippedData.splice(irow,1)
                        else
                            strippedData = tableData
                        # console.log(empty)
                    # console.log(tableData)

                    $("#options_value").val(JSON.stringify(strippedData))
        })
