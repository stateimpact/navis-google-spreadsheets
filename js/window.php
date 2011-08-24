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
            <label for="source">Source: </label>
            <input type="text" name="source" value="" id="source" />
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
        
        $('#cancel').click(function() {
            tinyMCEPopup.close();
        });
        
        // hide extra fields in the parent form so we can save
        // to postmeta
        $('form#options').submit(function(e) {
            e.preventDefault();
            var args = [shortcode_format('url', '"' + $('input#url').val() + '"')];
            var source = $('input#source').val();
            if (source) args.push(shortcode_format('source', '"' + source + '"'));
            var shortcode = "[spreadsheet " + args.join(' ') + "]";
            window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, shortcode);
            tinyMCEPopup.editor.execCommand('mceRepaint');
            tinyMCEPopup.close();
        });
    });
    </script>
    
</body>
</html>

