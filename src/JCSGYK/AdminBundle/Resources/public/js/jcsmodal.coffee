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
            width: "600"
            left: "center"
            target: ".modal"
            load: false
            speed: 0
            closeSpeed: 0

    visible: ->
        return $(".modal").is(":visible")

    load: ->
        $(".modal").overlay().load()

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
