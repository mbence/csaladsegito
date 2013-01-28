###
    Option storage
###
JcsOpt =
    cookieName: "jcsgyk"
    storage: "cookie"

    get: (param) ->
        if "cookie" == @storage
            return @getCookie(param)

    set: (param, value) ->
        if "cookie" == @storage
            return @setCookie(param, value)

    getCookie: (param = null) ->
        options = JSON.parse($.cookie(@cookieName))
        options ?= {}

        return if param? then options[param] ? 0 else options

    setCookie: (param, value) ->
        options = @get()
        options[param] = value
        $.cookie(@cookieName, JSON.stringify(options), { expires: 365, path: '/' })

