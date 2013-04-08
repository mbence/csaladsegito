###
    Creates the menu tabs with jquery.tools, and adds the assistance / inquiry button click functionality

    Called by main.coffee on document.ready()
###
JcsMenu =
    init: ->
        #JcsMenu.menu()
        JcsMenu.inquiry()

        # close the vertical-submenus on click
        $(document).on "click", ->
            $(".sub-vertical").hide()


    ###
        Set up the menu tabs
    ###
    menu: ->
        # find the active tab selected by the current class
        n = actTab = 0
        $("#header .menu ul.menutabs > li.mi").each( ->
            if !$("a", this).hasClass('current')
                n++
            else
                actTab = n

                return false
        )
        # init menu tabs
        $("#header .menu ul.menutabs").tabs("#header .menu .menupanes > div", {
            initialIndex: actTab,
            effect: 'fade'
        })
        # add menupanes clicks (only visual tuing)
        $("#header .menu .menupanes a.smi").click( ->
            $("#header .menu .menupanes a").removeClass('current')
            $(this).addClass('current')

            true
        )

        true

    ###
        Add the inquiry ajax actions
    ###
    inquiry: ->
        $(".inquiry a").click( ->
            if !$(this).hasClass('animbutton') && $(this).attr('href')
                $(this).addClass('animbutton')
                that = this
                $.post($(this).attr('href'), (data) ->
                    $(that).removeClass('animbutton')
                    AjaxBag.showNotice(data)
                    if $(".inquiry-stats").length and $(".inquiry-stats").data("action")
                        $.get($(".inquiry-stats").data("action"), (data) ->
                            $(".inquiry-stats").html(data)
                            JcsChart.init()
                        )
                )

            false
        )

    submenu: ->
        $(".morebutton").off("click").on("click", (event) ->
            event.stopPropagation()
            $(".sub-vertical", this).toggle()
        )