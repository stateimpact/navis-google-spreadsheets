<?php
/***
 * Plugin Name: Navis Google Spreadsheet Embed
 * Description: A safe and easy way to embed Google Spreadsheets in posts
 * Version: 0.1
 * Author: Chris Amico
 * License: GPLv2
***/
/*
    Copyright 2011 National Public Radio, Inc. 

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



// Filesystem path to this plugin.
define( 'NAVIS_SPREADSHEETS_ROOT', WP_PLUGIN_DIR . '/' . dirname(__FILE__) );

class Navis_Google_Spreadsheets {
    
    static $include_tablesorter;
    
    function __construct() {
        
        add_action('init', array(&$this, 'add_stylesheet'));
        
        add_action('save_post', array(&$this, 'save'));
        
        add_action( 'init', array(&$this, 'register_tinymce_filters'));
        
        add_filter('wp_footer', array(&$this, 'include_dependencies'));
        
        add_shortcode('spreadsheet', array(&$this, 'shortcode'));
        
    }
    
    function register_tinymce_filters() {
        add_filter('mce_external_plugins', 
            array(&$this, 'add_tinymce_plugin')
        );
        add_filter('mce_buttons', 
            array(&$this, 'register_button')
        );
        
    }
    
    function add_tinymce_plugin($plugin_array) {
        $plugin_array['spreadsheet'] = plugins_url(
            'js/tinymce-tables.js', __FILE__);
        return $plugin_array;
    }
    
    function register_button($buttons) {
        array_push($buttons, '|', "spreadsheet");
        return $buttons;
    }
    
    function save($post_id) {
        $post = get_post($post_id);

        if (preg_match_all('/'.get_shortcode_regex().'/', $post->post_content, $matches)) {
            $tags = $matches[2];
            $args = $matches[3];

            foreach($tags as $i => $tag) {
                if ($tag == "spreadsheet") {
                    $atts = shortcode_parse_atts($args[$i]);
                    
                    // extract a url, or bail
                    $url = $this->get_url($atts);
                    if (!$url) continue;
                    
                    $rows = $this->fetch_csv($url);
                    if ($rows !== false) {
                        update_post_meta($post_id, $url, $rows);
                    }
                }
            }
            
        }
        
    }
    
    function get_url($options) {
        if ($options['key']) {
            $url = "https://spreadsheets.google.com/pub?key={$options['key']}&output=csv";
            if ($options['sheet'] !== null) {
                $url .= "&single=true&gid={$options['sheet']}";
            }
        } else {
            $url = $options['url'];
        }
        
        return $url;
    }
    
    function fetch_csv($url) {
        if (false === ($file = fopen($url, 'r')) ) {
            return false; // bail on error
        }
        
        // grab all rows
        $rows = array();
        while (($row = fgetcsv($file)) !== false) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    function render_table($rows, $options, $caption) {
        
        if ($options['strip'] > 0) { $rows = array_slice($rows, $options['strip']); } // discard

        $ir = 1; // row number counter
        $ic = 1; // column number counter

        // Prepend a space character onto the 'class' value, if one exists.
        if (!empty($options['class'])) { $options['class'] = " {$options['class']}"; }
        $classes = array($options['class']);
        if ($options['paginate']) {
            array_push($classes, 'paginated');
        }
        
        if ($options['filter']) {
            array_push($classes, 'filter');
        }
        
        if ($options['sortable']) {
            array_push($classes, 'sortable');
        }
        
        $classnames = implode(' ', $classes);
        $html = "";
        if ($options['filter'] && $options['sortable']) {
            $html .= "<p class=\"table-filter\">";
            $html .= "<label for=\"search\">Search:</label> ";
            $html .= "<input name=\"search\" type=\"search\" placeholder=\"Search this table\">";
            $html .= "</p>";
        }
        if ($options['paginate'] && $options['sortable']) {
            $html .= '<div class="pager">';
            $html .= '  <form>';
            $html .= '      <div class="pagesize-wrapper">';
            $html .= '          <span>Show </span>';
            $html .= '          <select class="pagesize">';
            $html .= '             <option value="25">25</option>';
            $html .= '             <option value="50">50</option>';
            $html .= '             <option  value="100">100</option>';
            $html .= '          </select>';
            $html .= '          <span> rows.</span>';
            $html .= '      </div>';
            $html .= '  </form>';
            $html .= '</div>';
        }
        $html  .= "<table class=\"{$classnames}\" summary=\"{$options['summary']}\">";
        if (!empty($caption)) {
            $html .= "<caption>$caption</caption>";
        }
        
        // thead
        $html .= "<thead><tr class=\"row-$ir " . $this->odd_even($ir) . "\">";
        $ir++;
        $table_head = array_shift($rows);
        foreach ($table_head as $v) {
            $html .= "<th class=\"col-$ic " . $this->odd_even($ic) . "\"><div>$v</div></th>";
            $ic++;
        }
        $html .= "</tr></thead>";
        
        // tbody
        $html .= "<tbody>";
        foreach ($rows as $v) {
            $html .= "<tr class=\"row-$ir " . $this->odd_even($ir) . "\">";
            $ir++;
            $ic = 1; // reset column counting
            foreach ($v as $td) {
                $html .= "<td class=\"col-$ic " . $this->odd_even($ic) . "\">$td</td>";
                $ic++;
            }
            $html .= "</tr>";
        }
        $html .= '</tbody>';
        $html .= '</table>';
        if ($options['paginate']) {
            $html .= '<div class="pager">';
            $html .= '      <div class="pagenav-wrapper">';
            $html .= '          <a href="#" class="prev">Previous</a>';
            $html .= '          <input type="text" class="pagedisplay" readonly="readonly"/>';
            $html .= '          <a href="#" class="next">Next</a>';
            $html .= '      </div>';
            $html .= '</div>';
        }

        if ($options['source']) {
            $html .= "<p class=\"source\">Source: {$options['source']}</p>";
        }

        return $html;
    }

    function odd_even ($num) {
        return ((int) $num % 2) ? 'odd' : 'even'; // cast to integer just in case
    }

    function shortcode($atts, $content = null) {
        global $post;
        self::$include_tablesorter = true;
                
        $options = shortcode_atts(array(
            'key'      => null,                // Google Doc ID
            'url'      => null,
            'class'    => 'tablesorter',        // Container element's custom class value
            'sheet'    => null,                // Sheet ID for a Google Spreadsheet, if only one
            'summary'  => 'Google Spreadsheet', // If spreadsheet, value for summary attribute
            'source'   => '',                   // Source, printed below the table
            'sortable' => true,
            'filter'   => false,                // allow filtering
            'paginate' => false,             // pagination, off by default
            'strip'    => 0,                     // If spreadsheet, how many rows to omit from top
            'page'     => null              // here for backwards compatability
        ), $atts);
        
        if ($options['page'] !== null) {
            $options['sheet'] = $options['page'];
        }
        $url = $this->get_url($options);

        # wordpress seems to insert junk characters
        $url = str_replace('#038;', '', $url);
        $rows = get_post_meta($post->ID, $url, true);
        
        if ($rows) return $this->render_table($rows, $options, $content);
    }
    
    function add_stylesheet() {
        $css = plugins_url( 'css/style.css', __FILE__);
        wp_enqueue_style(
            'tablesorter', $css, array(), '2.0.5'
        );
    }
    
    function include_dependencies() {
        if (!self::$include_tablesorter) return;
        
        $tablesorter = plugins_url( 'js/jquery.tablesorter.min.js', __FILE__ );
        $config = plugins_url( 'js/tablesorter-config.js', __FILE__ );
        $filter = plugins_url( 'js/jquery.tablesorter.multipagefilter.js', __FILE__);
        $pager = plugins_url( 'js/jquery.tablesorter.pager.js', __FILE__);
        wp_register_script(
            'tablesorter', $tablesorter, array('jquery'), '2.0.5', true
        ); 
        wp_register_script(
            'tablesorter-config', $config, array('jquery', 'tablesorter'), '0.1', true
        );
        wp_register_script( 
            'multipagefilter', $filter, array('jquery', 'tablesorter'), '0.1', true
        );
        wp_register_script(
            'tablepager', $pager, array('jquery', 'tablesorter'), '0.1', true
        );
        wp_print_scripts( 'tablesorter' );
        wp_print_scripts( 'tablesorter-config' );
        wp_print_scripts( 'multipagefilter' );
        wp_print_scripts( 'tablepager' );

    }
    
}

new Navis_Google_Spreadsheets;
