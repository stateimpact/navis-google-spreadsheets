<?php
if (isset($_SERVER['HTTPS'])) {
    $SITEURL = ( $_SERVER[ 'HTTPS' ] ) ? 'https://' : 'http://';
} else {
    $SITEURL = 'http://';
}
$SITEURL .= $_SERVER[ 'HTTP_HOST' ] or $_SERVER[ 'SERVER_NAME' ];
$SITEURL .= $_GET[ 'wpbase' ];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Insert a Spreadsheet</title>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
    <script src="<?php echo $SITEURL; ?>wp-includes/js/tinymce/tiny_mce_popup.js"></script>
    <script src="<?php echo $SITEURL; ?>wp-includes/js/tinymce/utils/form_utils.js"></script>
    <style>
    form p {
        font-size: 1.5em;
    }
    </style>
</head>
<body onload="tinyMCEPopup.executeOnLoad('init();)'); document.body.style.display='';">
    <form id="options">
        <p>                
            <label for="url">Spreadsheet CSV URL: </label>
            <input type="text" name="url" value="" id="url" />
        </p>
        <p>                
            <label for="key">Spreadsheet key: </label>
            <input type="text" name="key" value="" id="key" />
        </p>
        <p>                
            <label for="url">Page: </label>
            <input type="text" name="page" value="" id="page" />
        </p>
        <p>
            <label for="source">Source: </label>
            <input type="text" name="source" value="" id="source" />
        </p>
        <p>
            <label for="paginate">Paginate: </label>
            <input type="checkbox" name="paginate" value="1" id="paginate" />
        </p>
        <p>
            <label for="filter">Allow filtering: </label>
            <input type="checkbox" name="filter" value="1" id="filter" />
        </p>
        
        <div class="mceActionPanel">
            <div style="float: left">
                <input type="button" id="cancel" name="cancel" value="Cancel" />
            </div>
            <div style="float: right">
                <input type="submit" id="insert" name="insert" value="Insert" />
            </div>
        </div>
    </form>
    <script>    
    function shortcode_format(key, value) {
        return key + "=" + value + " ";
    }
    
    $(function() {
        var inst = tinyMCE.getInstanceById('content');
        var html = inst.selection.getContent();
        
        $('input#url').change(function(e) {
            var key, page, url;
            url = $(this).val();
            if (!url) return;
            key = url.match(/key=\w+/)[0];
            page = url.match(/gid=\d+/)[0];
            if (!key || key === undefined) return;
            
            $('#key').val(key.split('=')[1]);
            $('#page').val(page.split('=')[1]);
        });
        
        $('#cancel').click(function() {
            tinyMCEPopup.close();
        });
        
        // hide extra fields in the parent form so we can save
        // to postmeta
        $('form#options').submit(function(e) {
            e.preventDefault();
            /***
            var args = [shortcode_format('key', '"' + $('input#key').val() + '"')];
            var source = $('input#source').val();
            var page = $('input#page').val();
            if (source) args.push(shortcode_format('source', '"' + source + '"'));
            if (page) args.push(shortcode_format('page', page));
            ***/
            args = [];
            // hash of fields: quoted
            var fields = {
                'key': true, 
                'source': true, 
                'filter': false, 
                'paginate': false, 
                'page': false
            };
            for (var field in fields) {
                var value = $('input#' + field).val();
                if (value) {
                    if (fields[field]) {
                        args.push(shortcode_format(field, '"' + value + '"'));
                    } else {
                        args.push(shortcode_format(field, value));
                    }
                }
            }
            console.log(args);
            var shortcode = "[spreadsheet " + args.join(' ') + "]";
            window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, shortcode);
            tinyMCEPopup.editor.execCommand('mceRepaint');
            tinyMCEPopup.close();
        });
    });
    </script>
    
</body>
</html>

