JcsChart =
    init: ->
        if day_charts?
            for day in day_charts
                @drawDay(day)
        if month_charts?
            @drawMonth(month_charts)

    drawDay: (opt) ->
        $.jqplot(opt.selector,  opt.data, {
            title: opt.title
            seriesDefaults:
                renderer: $.jqplot.BarRenderer
                pointLabels: {show: true, location: "e", edgeTolerance: -15}
                shadowAngle: 135
                rendererOptions:
                    barDirection: "horizontal"
                    shadowDepth: 4
            axes:
                yaxis:
                    renderer: $.jqplot.CategoryAxisRenderer
                    ticks: opt.tick
                xaxis:
                    max: opt.max
            series: JSON.parse(opt.colors)
        })

    drawMonth: (opt) ->
        $.jqplot(opt.selector,  opt.data, {
            stackSeries: true
            axesDefaults:
                show: false
            title: opt.title
            seriesDefaults:
                renderer: $.jqplot.BarRenderer
                rendererOptions:
                    barMargin: 0
                pointLabels: {show: true}
            axes:
                xaxis:
                    renderer: $.jqplot.CategoryAxisRenderer
                    max: opt.max
                yaxis:
                    padMin: 0
            series: JSON.parse(opt.colors)
        })