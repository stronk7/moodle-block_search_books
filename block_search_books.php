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
 * @package    blocks
 * @subpackage search_books
 * @copyright  2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This block enables searching within all the books in a given course
 */
class block_search_books extends block_base {
    function init() {
        $this->title = get_string('blockname','block_search_books');
        $this->version = 20090818;
    }

    function has_config() {return false;}

    function applicable_formats() {
        return (array('site-index' => true, 'course-view-weeks' => true, 'course-view-topics' => true));
    }

    function get_content() {
        global $CFG, $USER;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $course = get_record('course', 'id', $this->instance->pageid);

        $searchbooks = get_string('bookssearch', 'block_search_books');

        $rowstart = '<tr><td align="center">';
        $rowend = '</td></tr>';

        $coursefield = '<input type="hidden" name="courseid" value="'.$course->id.'">';
        $pagefield = '<input type="hidden" name="page" value="0">';
        $searchbox = '<input type="text" name="query" size="20" maxlength="255" value="">';
        $submitbutton = '<br /><input type="submit" name="submit" value="'.$searchbooks.'">';

        $row2content = $coursefield.$pagefield.$searchbox.$submitbutton;

        $row2 = $rowstart.$row2content.$rowend;

        $table = '<table>'.$row2.'</table>';
        $form = '<form method="GET" action="'.$CFG->wwwroot.'/blocks/search_books/search_books.php">'.$table.'</form>';
        $this->content->text = $form;

        return $this->content;
    }
}

?>
