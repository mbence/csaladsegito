JcsAdmin =
    init: ->
        # users
        if $("#userlist").length
            $("#useredit").hide()
            @setupUsers()
            $("#userlist table").tablesorter()
            @setHeights()
            $(window).resize =>
                @setHeights()
    #        $("#userlist tbody tr")[1].click()

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
            if !$(".paramsave", this).hasClass('form-saving')
                $(".paramsave", this).addClass('form-saving')
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
            if $(this).data("userid")
                event.stopPropagation()
                $("#useredit").show()
                $("#useredit .loading").show()
                $("#useredit .usercontent").hide()

                # start the ajax request
                $.get($("#getuserform").attr("action") + "/" + $(this).data("userid"), (data) ->
                    $("#useredit .loading").hide()
                    $("#useredit .usercontent").html(data).show()
                    JcsAdmin.setupForm()
                ).error( (data) ->
                    # there was some error :(
                    AjaxBag.showError(data.statusText)
                    $("#useredit .loading").hide()
                    $("#useredit .usercontent").html("")
                )
                $("#userlist tr").removeClass("current cursor")
                $(this).addClass("current cursor")
        )

        # new user
        $("#new_user").click ->
            $("#useredit").show()
            $("#useredit .loading").show()
            $("#useredit .usercontent").hide()

            # start the ajax request
            $.get($(this).attr("href"), (data) ->
                $("#useredit .loading").hide()
                $("#useredit .usercontent").html(data).show()
                JcsAdmin.setupForm()
            ).error( (data) ->
                # there was some error :(
                AjaxBag.showError(data.statusText)
                $("#useredit .loading").hide()
                $("#useredit .usercontent").html("")
            )

            false

    setHeights: ->
        h = $(window).innerHeight() - $('#header').outerHeight() - $('#colophon').outerHeight() - 36
        # set heights
        $('#userlist').height(h)
        $('#useredit').height(h)
        true

    setupForm: ->
        $("#useredit .formbuttons .cancel").add("#useredit .close").click ->
            $("#useredit .usercontent").html("")
            $("#userlist tr").removeClass("current cursor")
            $("#useredit").hide()

        $("#useredit #editform").submit ->
            if !$("#useredit .formbuttons .usersave").hasClass('form-saving')
                $("#useredit .formbuttons .usersave").addClass('form-saving')
                $.post $(this).attr('action'), $(this).serialize(), (data) =>
                    if "success" == data
                        document.location.reload()
                    else
                        $("#useredit .usercontent").html(data)
                        JcsAdmin.setupForm()
            false