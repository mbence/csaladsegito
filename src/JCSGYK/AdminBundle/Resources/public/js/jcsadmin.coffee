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

    setupParams: ->
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

        $(".paramlist").hide()
        $("#parameter-groups li")[0].click()

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