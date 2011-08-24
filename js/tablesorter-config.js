jQuery(function($) {
    // basic
    var tables = $('.post table').tablesorter({ 
        textExtraction: function(node) {
            var text = $(node).text();
            return text.replace(/,|\$|%/g, '');
        }
    })
    .tablesorterMultiPageFilter({
        filterSelector: $('.filter input')
    });
    
    // paginated
    tables.filter('.paginated').tablesorterPager({
        container: $(".pager"),
        positionFixed: false,
        size: 25,
    });
    
});
