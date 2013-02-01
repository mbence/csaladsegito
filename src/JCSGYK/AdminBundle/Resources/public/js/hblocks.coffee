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
            HBlocks.scrollTo(1)
        $("#clientblock").click ->
            HBlocks.scrollTo(2)
        $("#problemblock").click ->
            HBlocks.scrollTo(3)
        $("#eventblock").click ->
            HBlocks.scrollTo(4)

        # keyboard events
        $(document).keydown (event) ->
            HBlocks.setKeys(event)
        @setCloseButtons()

    ###
        Set the keyboard actions
    ###
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
            # we step to the previous block if possible
            if $(".contentscroller > .current").prev().is(":visible")
                $(".contentscroller > .current").prev().click()
        # right
        if 39 == event.which
            # if the cursor is on the actually selected and opened element, we just step right
            if $(".contentscroller > .current .walkable tr.cursor").hasClass("current") and $(".contentscroller > .current").next().is(":visible")
                $(".contentscroller > .current").next().click()
            # otherwise we click the current element
            else if $(".contentscroller > .current .walkable tr.cursor").length
                $(".contentscroller > .current .walkable tr.cursor").click()
        # up
        if 38 == event.which
            # only do anything if there is a list we can walk over, and there is a cursor too
            if $(".contentscroller > .current .walkable tr").length and $(".contentscroller > .current .walkable tr.cursor").length
                # if there is a previous element, we step on it by moving the "cursor" class
                if $(".contentscroller > .current .walkable tr.cursor").prev().is(":visible")
                    event.stopPropagation()
                    event.preventDefault()
                    $(".contentscroller > .current .walkable tr.cursor").removeClass("cursor").prev().addClass("cursor").focus()
                # otherwhise if we are on the search block, we focus on the search field
                else if $(".contentscroller > .current .searchfield").length
                    event.stopPropagation()
                    event.preventDefault()
                    $(".contentscroller > .current .searchfield").focus()
                    $(".contentscroller > .current .walkable tr.cursor").removeClass("cursor")
        # down
        if 40 == event.which
            if $(".contentscroller > .current .walkable tr").length
                # blur the search field
                $(".contentscroller > .current .searchfield").blur()
                # if we don't have a cursor, we select the first row in the list
                if $(".contentscroller > .current .walkable tr.cursor").length == 0
                    event.stopPropagation()
                    event.preventDefault()
                    $(".contentscroller > .current .walkable tr").first().addClass("cursor").focus()
                # otherwise if there is a row next we step on it
                else if $(".contentscroller > .current .walkable tr.cursor").next().is(":visible")
                    event.stopPropagation()
                    event.preventDefault()
                    $(".contentscroller > .current .walkable tr.cursor").removeClass("cursor").next().addClass("cursor").focus()
        # enter
        if 13 == event.which
            # enter will select a row, it is the same action as if we clicked it
            if $(".contentscroller > .current .walkable tr.cursor").length
                event.stopPropagation()
                event.preventDefault()
                $(".contentscroller > .current .walkable tr.cursor").click()

    ###
        Set the close button actions
    ###
    setCloseButtons: ->
        # close button functionality
        $("#clientblock .close").click (e) =>
            e.stopPropagation()
            $("#search-results tr").removeClass("current")
            @closeBlock(4)
            @closeBlock(3)
            @closeBlock(2, true)

        $("#problemblock .close").click (e) =>
            e.stopPropagation()
            $("#problem-list tr").removeClass("current")
            @closeBlock(4)
            @closeBlock(3, true)

        $("#eventblock .close").click (e) =>
            e.stopPropagation()
            $("#event-list tr").removeClass("current")
            @closeBlock(4, true)

    ###
        Close a block with or without animation
        To hide a block and clear it's content, call with animate = false
        Closing with animation also activates the previous block
    ###
    closeBlock: (n, animate = false) ->
        block = $(".contentscroller > div:nth-child(" + n + ")")
        if not animate
            # no animation, hide the block
            block.hide()
            # and clear the content container
            $("div > div:nth-child(4)", block).html("")
            # then recalcualte the scroller div size
            @setBlockSizes()
        else
            blockW = @blockW()
            # scroll the right side of the previous block to the right of the screen (if possible)
            x = blockW * (n-1) - $("#content").width()
            # only animate if we can scroll at all
            anim_speed = if $(".contentwrapper").prop("scrollLeft") == 0 then 0 else 300
            $(".contentwrapper").animate({scrollLeft: x}, anim_speed, 'linear', =>
                # animation done, activate the previous block
                if $(block).prev().is(":visible")
                    $(block).prev().click()
                # and hide the actial one by calling this function without animations
                @closeBlock(n, false)
            )

    ###
        Calculate the width of the blocks
    ###
    blockW: ->
        blockW = Math.round(($(window).innerWidth() - 40) * 0.45)
        if blockW < 470
            blockW = 470
        else if blockW > 600
            blockW = 600

        return blockW

    ###
        Count the visible blocks, and set the widths for scrolling
    ###
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

    ###
        Set the heights of the blocks
    ###
    setHeights: ->
        h = $(window).innerHeight() - $('#header').outerHeight() - $('#colophon').outerHeight() - 36
        # set heights
        $('#search-results').height(h - 50)
        $('#clientblock').height(h)
        $('#problemblock').height(h)
        $('#eventblock').height(h)

        true

    ###
        Scroll to the given block, and also set is as current
        1 - search
        2 - client
        3 - problems
        4 - events
    ###
    scrollTo: (block) ->
        blockW = @blockW()
        # try to center the selected block
        x = Math.round((block - 1) * blockW - (($("#content").width() - blockW) / 2))
        $(".contentwrapper").animate({scrollLeft: x}, 500)
        # if not already set, add the "current" class to the selected block
        if not $(".contentscroller > div:nth-child(" + block + ")").hasClass("current")
            $(".contentscroller > div").removeClass("current")
            $(".contentscroller > div:nth-child(" + block + ")").addClass("current")
            # if there is a list, focus on it's current element
            if $(".contentscroller > .current .walkable .current").length
                $(".contentscroller > .current .walkable .current").focus()
            # or focus on the one selected by the cursor
            else if $(".contentscroller > .current .walkable .cursor").length
                $(".contentscroller > .current .walkable .cursor").focus()
            # or focus on the block itself, if no current, and no cursor exists
            else
                $(".contentscroller > .current").focus()
