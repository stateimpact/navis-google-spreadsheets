jQuery(function($) {
    $('.post table').tablesorter({ 
        debug: true, 
        textExtraction: function(node) {
            var text = $(node).text();
            return text.replace(/,|\$|%/g, '');
        }
    });
});
