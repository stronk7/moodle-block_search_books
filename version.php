<?php

// This file is part of block_search_books,
// one contrib block for Moodle - http://moodle.org/
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
 * @package    block
 * @subpackage search_books
 * @copyright  2009 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2011092500; // The current block version (Date: YYYYMMDDXX)
$plugin->requires  = 2010112400; // Requires this Moodle version (v2.0.0)
$plugin->component = 'block_search_books';

$maturity = MATURITY_ALPHA;

$plugin->dependencies = array(
    'mod_book' =>  2011032000,     // Requires mod_block version (20_STABLE)
    'mod_glossary' => 2010111501); // Requires mod_glossary version (20_STABLE)
