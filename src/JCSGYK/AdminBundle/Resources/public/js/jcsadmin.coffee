JcsAdmin =
    init: ->
        @setupUsers()
        $("#userlist table").tablesorter()
        @setHeights()

        $("#userlist tbody tr")[1].click()

    setupUsers: ->
        $("#userlist tbody tr").click( (event) ->
            event.stopPropagation()
            $("#useredit .loading").show()
            $("#useredit .usercontent").hide()

            # start the ajax request
            $.post($("#getuserform").attr("action"), {id: $(this).data("userid")}, (data) ->
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