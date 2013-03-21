<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Add in Moodle 2 renderer to module
 * This is a bit of a hack, as for ease the renderer calls existing methods
 *
 * @package    mod
 * @subpackage refworks
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_refworks_renderer extends plugin_renderer_base {

    public function write_sidemenu($curfile = '', $cursharedacct = '') {
        refworks_base::write_sidemenu_renderer($curfile, $cursharedacct);
    }

    public function write_page_list($sorting, $curpage, $count, $showall, $url, $numitemsmax = 20, $sortform = true) {
        return refworks_display::write_page_list_renderer($sorting, $curpage, $count, $showall, $url, $numitemsmax, $sortform);
    }

    public function write_shared_accounts_list($accountsarray, $actiontype = self::noaction) {
        refworks_display::write_shared_accounts_list_renderer($accountsarray, $actiontype);
    }

    public  function write_folder_list($folderarray, $actiontype = self::noaction, $sharedfolderarray = array()) {
        refworks_display::write_folder_list_renderer($folderarray, $actiontype, $sharedfolderarray);
    }

    public function write_citation_list($xmlstring) {
        return refworks_display::write_citation_list_renderer($xmlstring);
    }

    public function write_formatted_reference_list($xmlstring) {
        return refworks_display::write_formatted_reference_list_renderer($xmlstring);
    }

    public function write_ref_list($xmlstring, $actiontype = self::noaction) {
        refworks_display::write_ref_list_renderer($xmlstring, $actiontype);
    }

    public function create_ref_item($refnode, $actiontype) {
        return refworks_display::create_ref_item_renderer($refnode, $actiontype);
    }

    public function create_shared_account_opts($accountarray) {
        return refworks_display::create_shared_account_opts_renderer($accountarray);
    }

    public function create_shared_dormant_account_opts($accountarray) {
        return refworks_display::create_shared_dormant_account_opts_renderer($accountarray);
    }

    public function create_ref_opts($refnode, $link, $folder='') {
        return refworks_display::create_ref_opts_renderer($refnode, $link, $folder);
    }

    public function create_folder_opts($foldername, $num=0) {
        return refworks_display::create_folder_opts_renderer($foldername, $num);
    }

    public function share_folder_opts($foldername, $num=0, $shared=false, $shareurlpath = '') {
        return refworks_display::share_folder_opts_renderer($foldername, $num, $shared, $shareurlpath);
    }

    public function create_ref_folderdisplay($refnode) {
        return refworks_display::create_ref_folderdisplay_renderer($refnode);
    }

    public function create_ref_select($refnode) {
        return refworks_display::create_ref_select_renderer($refnode);
    }
}