###
    Sets up the Event block actions
###
JcsTask =
    init: (sel = document)->
        $(".task-list tbody tr", sel).off("click").on "click", ->
            if $(this).data('url')
                document.location = $(this).data('url')
