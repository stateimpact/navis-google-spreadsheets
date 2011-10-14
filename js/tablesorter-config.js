jQuery(function($) {
    // basic
    var tables = $('table.sortable');
    
    // sorting
    tables.tablesorter({ 
        textExtraction: function(node) {
            var text = $(node).text();
            return text.replace(/,|\$|%/g, '');
        }
    });
    
    // filtered
    tables.filter('.filter').tablesorterMultiPageFilter({
        filterSelector: $('.table-filter input')
    });
    
    // paginated
    tables.filter('.paginated').tablesorterPager({
        container: $(".pager"),
        positionFixed: false,
        size: 25,
    });
    
});
