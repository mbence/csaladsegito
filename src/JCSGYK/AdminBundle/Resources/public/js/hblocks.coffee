###
    Horizontal blocks
###
HBlocks =
    init: ->
        # set sizes and win resize event
        @setBlockSizes()
        @setHeights()

        $(window).resize =>
            @setBlockSizes()
            @setHeights()

        # init horizontal blocks
        $("#searchblock").click ->
            HBlocks.scrollTo(0)
        $("#clientblock").click ->
            HBlocks.scrollTo(1)
        $("#problemblock").click ->
            HBlocks.scrollTo(2)
        $("#eventblock").click ->
            HBlocks.scrollTo(3)

        # keyboard events
        $(document).keydown (event) ->
            HBlocks.setKeys(event)
        @setCloseButtons()

    setKeys: (event) ->
        # close current block on ESC
        if 27 == event.which
            if $(".contentscroller > .current .close").length
                event.stopPropagation()
                event.preventDefault()
                $(".contentscroller > .current .close").click()
            # clear the search field
            else if $(".contentscroller > .current #quicksearch").length
                event.stopPropagation()
                event.preventDefault()
                $("#quicksearch .nf-clear").click()
                $("#quicksearch .searchfield").focus()

        # left
        if 37 == event.which
            if $(".contentscroller > .current").prev().is(":visible")
                $(".contentscroller > .current").prev().click()
        # right
        if 39 == event.which
            if $(".contentscroller > .current .walkable tr.cursor").hasClass("current") and $(".contentscroller > .current").next().is(":visible")
                $(".contentscroller > .current").next().click()
            else
                if $(".contentscroller > .current .walkable tr.cursor").length
                    $(".contentscroller > .current .walkable tr.cursor").click()
        # up
        if 38 == event.which
            if $(".contentscroller > .current .walkable tr").length
                if $(".contentscroller > .current .walkable tr.cursor").length == 0
                    event.stopPropagation()
                    event.preventDefault()
                    $(".contentscroller > .current .walkable tr").last().addClass("cursor").focus()
                else
                    if $(".contentscroller > .current .walkable tr.cursor").prev().is(":visible")
                        event.stopPropagation()
                        event.preventDefault()
                        $(".contentscroller > .current .walkable tr.cursor").removeClass("cursor").prev().addClass("cursor").focus()
                    else
                        $(".contentscroller > .current .searchfield").focus()
                        $(".contentscroller > .current .walkable tr.cursor").removeClass("cursor")
        # down
        if 40 == event.which
            if $(".contentscroller > .current .walkable tr").length
                $(".contentscroller > .current .searchfield").blur()
                if $(".contentscroller > .current .walkable tr.cursor").length == 0
                    event.stopPropagation()
                    event.preventDefault()
                    $(".contentscroller > .current .walkable tr").first().addClass("cursor").focus()
                else
                    if $(".contentscroller > .current .walkable tr.cursor").next().is(":visible")
                        event.stopPropagation()
                        event.preventDefault()
                        $(".contentscroller > .current .walkable tr.cursor").removeClass("cursor").next().addClass("cursor").focus()
        # enter
        if 13 == event.which
            if $(".contentscroller > .current .walkable tr.cursor").length
                event.stopPropagation()
                event.preventDefault()
                $(".contentscroller > .current .walkable tr.cursor").click()

        #console.log event.which

    setCloseButtons: ->
        # close button functionality
        $("#clientblock .close").click (e) =>
            e.stopPropagation()
            $("#clientblock").hide()
            $("#clientblock .clientcontent").html("")
            $("#search-results tr").removeClass("current")
            $("#problemblock .close").click()
            $("#searchblock").click()
            @setBlockSizes()

        $("#problemblock .close").click (e) =>
            e.stopPropagation()
            $("#problemblock").hide()
            $("#problemblock .problemcontent").html("")
            $("#eventblock .close").click()
            $("#clientblock").click()
            @setBlockSizes()

        $("#eventblock .close").click (e) =>
            e.stopPropagation()
            $("#eventblock").hide()
            $("#eventblock .eventcontent").html("")
            $("#problemblock").click()
            @setBlockSizes()

    blockW: ->
        blockW = Math.round(($(window).innerWidth() - 40) * 0.45)
        if blockW < 470
            blockW = 470
        else if blockW > 600
            blockW = 600

        return blockW

    setBlockSizes: ->
        blockW = @blockW()

        # count visible blocks
        blockNum = $(".contentscroller > div:visible").length
        scrollerW = blockW * blockNum
        $(".contentscroller").width(scrollerW)

        # if there is a horisontal scrollbar, reduce the bottom padding
        if scrollerW > $(window).innerWidth() - 40
            $("#content").css('padding-bottom', '26px')
        else
            $("#content").css('padding-bottom', '40px')

        # set block widths
        $(".contentscroller > div:visible").width(blockW)

        true

    setHeights: ->
        h = $(window).innerHeight() - $('#header').outerHeight() - $('#colophon').outerHeight() - 36
        # set heights
        $('#search-results').height(h - 50)
        $('#clientblock').height(h)
        $('#problemblock').height(h)
        $('#eventblock').height(h)

        true

    scrollTo: (block) ->
        console.log $(document.activeElement)
        blockW = @blockW()
        x = Math.round(block * blockW - (($("#content").width() - blockW) / 2))
        $(".contentwrapper").animate({scrollLeft: x}, 500)
        bch = block + 1
        if not $(".contentscroller > div:nth-child(" + bch + ")").hasClass("current")
            $(".contentscroller > div").removeClass("current")
            $(".contentscroller > div:nth-child(" + bch + ")").addClass("current")

            if $(".contentscroller > .current .walkable .current").length
                $(".contentscroller > .current .walkable .current").focus()
            else if $(".contentscroller > .current .walkable .cursor").length
                $(".contentscroller > .current .walkable .cursor").focus()
            else
                $(".contentscroller > .current").focus()
