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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
    <script src="<?php echo $SITEURL; ?>wp-includes/js/tinymce/tiny_mce_popup.js"></script>
    <script src="<?php echo $SITEURL; ?>wp-includes/js/tinymce/utils/form_utils.js"></script>
    <style>
    div {
        margin-bottom: 1.5em;
    }
    form p {
        font-size: 1.25em;
        margin-bottom: .5em;
        margin-top: .25em;
    }
    
    form p.help {
        font-size: 1em;
        color: #555;
    }
    </style>
</head>
<body onload="tinyMCEPopup.executeOnLoad('init();)'); document.body.style.display='';">
    <form id="options">
        <div>
            <p>                
                <label for="url">Google Spreadsheet URL: </label>
                <input type="text" name="url" value="" id="url" />
            </p>
            <p class="help">Paste in a spreadsheet URL. Key and sheet (below) will be filled in automatically.</p>
        </div>
        <div>
            <p>                
                <label for="key">Spreadsheet key: </label>
                <input type="text" name="key" value="" id="key" />
            </p>
            <p class="help">Add a URL (above) to set this automatically. Only change this field if you know what you're doing.</p>
        </div>
        <div>
            <p>                
                <label for="url">Sheet: </label>
                <input type="text" name="sheet" value="" id="sheet" />
            </p>
            <p class="help">Zero indexed, so sheet zero is first, one is second, etc</p>
        </div>
        <div>
            <p>
                <label for="source">Source: </label>
                <input type="text" name="source" value="" id="source" />
            </p>
            <p class="help">Where did you get this data?</p>
        </div>
        <div>
            <p>
                <label for="sortable">Sortable: </label>
                <input type="checkbox" name="sortable" value="1" id="sortable" checked="checked"/>
            </p>
            <p class="help">Disable sorting. Note that this also disables pagination and filtering.</p>
        </div>
        <div class="requires-sortable">
            <p>
                <label for="paginate">Paginate: </label>
                <input type="checkbox" name="paginate" value="1" checked="checked" id="paginate" />
            </p>
            <p class="help">Split display into 25-row pages.</p>
        </div>
        <div class="requires-sortable">
            <p>
                <label for="filter">Allow filtering: </label>
                <input type="checkbox" name="filter" value="1" id="filter" />
            </p>
            <p class="help">Check this box to allow users to search this table.</p>
        </div>
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
            $('#sheet').val(page.split('=')[1]);
        });
        
        $('input#sortable').change(function(e) {
            if ( !$(this).attr('checked') ) {
                $('.requires-sortable').hide()
                    .find('input[type=checkbox]')
                    .attr('checked', false);
            } else {
                $('.requires-sortable').show();
            }
        });
        
        $('#cancel').click(function() {
            tinyMCEPopup.close();
        });
        
        // hide extra fields in the parent form so we can save
        // to postmeta
        $('form#options').submit(function(e) {
            e.preventDefault();
            args = [];
            
            var textfields = ['key', 'source'];
            var checkfields = ['filter', 'paginate', 'sortable']
            
            // key and source
            for (var i in textfields) {
                var field = textfields[i];
                var value = $('input#' + field).val();
                args.push(shortcode_format(field, '"' + value + '"'));
            };
            
            // sheet
            var sheet = $('input#sheet').val();
            if (sheet) {
                args.push(shortcode_format('sheet', sheet));
            }
            
            // sortable, paginate, filter
            for (var i in checkfields) {
                var field = checkfields[i];
                if ($('#' + field).is(':checked')) {
                    args.push(shortcode_format(field, 1));
                } else {
                    args.push(shortcode_format(field, 0));
                }
            }
            
            var shortcode = "[spreadsheet " + $.trim(args.join(' ')) + "]";
            window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, shortcode);
            tinyMCEPopup.editor.execCommand('mceRepaint');
            tinyMCEPopup.close();
        });
    });
    </script>
    
</body>
</html>

