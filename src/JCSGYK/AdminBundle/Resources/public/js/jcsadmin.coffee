JcsAdmin =
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
        if $("#parameter-groups").length
            @setupParams()

    ###
        setup the parameter editor
    ###
    setupParams: ->
        $(".paramlist").hide()
        # group selector
        $("#parameter-groups li").click (event) ->
            grp = $(this).data("groupid")
            if grp
                event.stopPropagation()
                $("#paramedit").show()
                $("#paramedit .loading").show()
                $("#paramedit .usercontent").hide()
                $(".paramlist").hide()
                $("#paramlist-" + grp).show();

                $("#parameter-groups li").removeClass("current cursor")
                $(this).addClass("current cursor")
        # add new parameter
        $(".new-param").click ->
            pos = $(this).parent().prev().children(".param").length + 1
            group = $(this).parent().parent().children("[name=group]").val()
            $(this).parent().prev().append($("#newparam-template").html().replace(/%pos%/g, pos).replace('%grp%', group))
            $(this).parent().prev().children().last().children(":text").focus()
            $( ".paramcontainer" ).sortable( "refresh" );

        # cancel button
        $("#parameter-lists .paramform .cancel").click ->
            $(".paramcontainer .newparam").remove()
            $( ".paramcontainer" ).sortable("cancel");

        # form submit animation
        $("#parameter-lists .paramform").submit ->
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

        $("#parameter-lists").show()
        $("#parameter-groups li.current").click()

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
