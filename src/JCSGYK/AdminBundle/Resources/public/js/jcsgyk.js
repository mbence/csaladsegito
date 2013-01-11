JCS = {
    // quicksearch timeout
    qto: null,

    init: function()
    {
        // search results height
        JCS.setBlockSizes();
        $(window).resize(function(){
            JCS.setBlockSizes();
        })

        // close buttons
        $("#personblock .close").click(function() {
            $("#personblock").hide();
            $("#personblock .personcontent").html("");
            $("#search-results tr").removeClass("current");
            JCS.setBlockSizes();
        });
        $("#problemblock .close").click(function() {
            $("#problemblock").hide();
            $("#problemblock .personcontent").html("");
            JCS.setBlockSizes();
        })

    },

    qSubmit: function()
    {
        //$("#quicksearch").submit();
    },
    setBlockSizes: function()
    {
        var blockW = Math.round(($(window).innerWidth() - 40) * 0.45);
        if (blockW < 470) {
            blockW = 470;
        }
        else if (blockW > 600) {
            blockW = 600;
        }
        // count visible blocks
        var blockNum = $(".contentscroller > div:visible").length;
        var scrollerW = blockW * blockNum;
        $(".contentscroller").width(scrollerW);
        // if there is a horisontal scrollbar...
        if (scrollerW > $(window).innerWidth() - 40) {
            $("#content").css('padding-bottom', '26px');
        }
        else {
            $("#content").css('padding-bottom', '40px');
        }
        $(".contentscroller > div:visible").width(blockW);

        // set heights
        $('#search-results').height($(window).innerHeight() - 186);
        $('#personblock').height($(window).innerHeight() - 136);
        $('#problemblock').height($(window).innerHeight() - 136);
    },

}


CsNav = function ()
{
    this.actBlock = 0;
    this.actRow = 0;
    this.numRows = 0;

    this.init = function()
    {

    };

    this.initRes = function(numRows)
    {
        this.numRows = numRows;
    };

    return this;
}
