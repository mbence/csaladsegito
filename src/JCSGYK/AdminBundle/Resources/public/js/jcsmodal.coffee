###
  Moda dialog functions
###
JcsModal =
    init: ->
        $(".modal").overlay
            # some mask tweaks suitable for modal dialogs
            mask:
                color: '#eceef7'
                loadSpeed: 0
                closeSpeed: 0
                opacity: 0.9
            closeOnClick: true
            width: "670px"
            left: "center"
            target: ".modal"
            load: false
            speed: 0
            closeSpeed: 0

    visible: ->
        return $(".modal").is(":visible")

    load: ->
        $(".modal").css
            "height": "auto"
            "width":  "auto"

        $(".modal").overlay().load()
        # restrict modal window height
        winH = $(window).innerHeight()
        #console.log winH, parseInt($(".modal").css("top")) + $(".modal").innerHeight()
        if parseInt($(".modal").css("top")) + $(".modal").innerHeight() > winH
            $(".modal").height(winH - 60)
            $(".modal").css("top", 10)
            # remove horizontal scroll
            w = $(".modal .modal-content > div").innerWidth()
            $(".modal").width(w + 10)

    close: ->
        $(".modal").overlay().close()

    setContent: (content) ->
        $(".modal .modal-content").html(content).show()

    find: (selector) ->
        return $(".modal .modal-content").find(selector)

    setCloseButton: ->
        $(".modal .modal-content .close").off("click").on "click", =>
            @close()

    onClose: (func) ->
        if $.isFunction(func)
            $(".modal").off('onClose').on('onClose', ->
                func()
            )
