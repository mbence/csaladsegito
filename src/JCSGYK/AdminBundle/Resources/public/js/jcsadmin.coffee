JcsAdmin =
    init: ->
        @setupUsers()
        $("#userlist").tablesorter()
        @setHeights()

    setupUsers: ->
        $("#userlist tbody tr").click( (event) ->
            event.stopPropagation()
            $("#useredit .loading").show()
            $("#useredit .usercontent").hide()

            # start the ajax request
            $.post($("#getuserform").attr("action"), {id: $(this).data("userid")}, (data) ->
                $("#useredit .loading").hide()
                $("#useredit .usercontent").html(data).show()
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
        $('#useredit').height(h)

        true