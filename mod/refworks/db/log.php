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
 * Definition of log events
 *
 * @package    mod
 * @subpackage refworks
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    // refworks instance log actions
    array('module'=>'refworks', 'action'=>'add', 'mtable'=>'refworks', 'field'=>'name'),
    array('module'=>'refworks', 'action'=>'update', 'mtable'=>'refworks', 'field'=>'name'),
    array('module'=>'refworks', 'action'=>'view', 'mtable'=>'refworks', 'field'=>'name'),
    array('module'=>'refworks', 'action'=>'view all', 'mtable'=>'refworks', 'field'=>'name'),
    array('module'=>'refworks', 'action'=>'manageref', 'mtable'=>'refworks', 'field'=>'name'),
    array('module'=>'refworks', 'action'=>'exportref', 'mtable'=>'refworks', 'field'=>'name'),
    array('module'=>'refworks', 'action'=>'createref', 'mtable'=>'refworks', 'field'=>'name'),
    array('module'=>'refworks', 'action'=>'createbib', 'mtable'=>'refworks', 'field'=>'name'),
    );
