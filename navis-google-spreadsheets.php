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
    
    function __construct() {
        
        add_action('save_post', array(&$this, 'save'));
        
        add_shortcode('spreadsheet', array(&$this, 'shortcode'));
        
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
            if ($options['gid']) {
                $url .= "&single=true&gid={$options['gid']}";
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

        $html  = "<table id=\"igsv-{$options['key']}\" class=\"igsv-table{$options['class']}\" summary=\"{$options['summary']}\">";
        if (!empty($caption)) {
            $html .= "<caption>$caption</caption>";
        }
        $html .= "<thead><tr class=\"row-$ir " . $this->odd_even($ir) . "\">";
        $ir++;
        $table_head = array_shift($rows);
        foreach ($table_head as $v) {
            $html .= "<th class=\"col-$ic " . $this->odd_even($ic) . "\"><div>$v</div></th>";
            $ic++;
        }
        $html .= "</tr></thead><tbody>";
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
        $html .= '</tbody></table>';

        return $html;
    }

    function odd_even ($num) {
        return ((int) $num % 2) ? 'odd' : 'even'; // cast to integer just in case
    }

    function shortcode($atts, $content = null) {
        global $post;
        
        $options = shortcode_atts(array(
            'key'      => null,                // Google Doc ID
            'url'      => null,
            'class'    => '',                   // Container element's custom class value
            'gid'      => false,                // Sheet ID for a Google Spreadsheet, if only one
            'summary'  => 'Google Spreadsheet', // If spreadsheet, value for summary attribute
            'strip'    => 0                     // If spreadsheet, how many rows to omit from top
        ), $atts);
        
        $url = $this->get_url($options);
        
        $rows = get_post_meta($post->ID, $url, true);
        if ($rows) return $this->render_table($rows, $options, $content);
    }
    
}

new Navis_Google_Spreadsheets;
