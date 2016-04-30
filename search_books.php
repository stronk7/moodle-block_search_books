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
 * Search books main script.
 *
 * @package    block_search_books
 * @copyright  2009 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/glossary/lib.php');
require_once($CFG->libdir.'/searchlib.php');

define('BOOKMAXRESULTSPERPAGE', 100);  // Limit results per page.

$courseid = required_param('courseid', PARAM_INT);
$query    = required_param('bsquery', PARAM_NOTAGS);
$page     = optional_param('page', 0, PARAM_INT);

function search($query, $course, $offset, &$countentries) {

    global $CFG, $USER, $DB;

    // Perform the search only in books fulfilling mod/book:read and (visible or moodle/course:viewhiddenactivities)
    $bookids = book_search_get_readble_books( $course );

    // transform the search query into safe SQL queries
    $searchterms = explode(" ",$query);
    $parser = new search_parser();
    $lexer = new search_lexer( $parser );

    if ( $lexer->parse( $query ) ) {
        $parsearray = $parser->get_parsed_array();
        list($messagesearch, $msparams) = 
            search_generate_SQL($parsearray, 'bc.title', 'bc.content', null, null, null, null, null, null);
    }

    // Main query, only to allowed books and not hidden chapters.
    $selectsql = "SELECT DISTINCT bc.*";
    $fromsql   = "  FROM {book_chapters} bc, {book} b";

    list( $insql, $inparams ) = $DB->get_in_or_equal($bookids, SQL_PARAMS_NAMED );
    
    $params = array_merge( Array( 'courseid' => $course->id ),
                           $inparams, $msparams);

    $wheresql  = "  WHERE b.course = :courseid
                          AND b.id $insql 
                          AND bc.bookid = b.id 
                          AND bc.hidden = 0
                          AND $messagesearch ";
    $ordersql  = "  ORDER BY bc.bookid, bc.pagenum";


    // Set page limits.
    $limitfrom = $offset;
    $limitnum = 0;
    if ( $offset >= 0 ) {
        $limitnum = BOOKMAXRESULTSPERPAGE;
    }
    $countentries = $DB->count_records_sql("select count(*) $fromsql $wheresql", $params);

    $allentries = $DB->get_records_sql("$selectsql $fromsql $wheresql $ordersql", $params, $limitfrom, $limitnum);

    return $allentries;
}

/**
 * return a list of book ids for the books which can be read/viewed
 *
 * @param stdClass $course  course object
 * @return array of book ids
 */

function book_search_get_readble_books( $course ) {

    $bookids = Array();

    if (! $books = get_all_instances_in_course('book', $course)) {
        notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'book')), "../../course/view.php?id=$course->id");
        die;
    }

    foreach ($books as $book) {
        $cm = get_coursemodule_from_instance("book", $book->id, $course->id);
        $context = context_module::instance($cm->id);
        if ($cm->visible || has_capability('moodle/course:viewhiddenactivities', $context)) {
            if (has_capability('mod/book:read', $context)) {
                $bookids[] = $book->id;
            }
        }
    }
    return $bookids;
}

//////////////////////////////////////////////////////////
// The main part of this script

$PAGE->set_pagelayout('standard');
$PAGE->set_url($FULLME);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}

require_course_login($course);

$strbooks = get_string('modulenameplural', 'book');
$searchbooks = get_string('bookssearch', 'block_search_books');
$searchresults = get_string('searchresults', 'block_search_books');
$strresults = get_string('results', 'block_search_books');
$ofabout = get_string('ofabout', 'block_search_books');
$for = get_string('for', 'block_search_books');
$seconds = get_string('seconds', 'block_search_books');

$PAGE->navbar->add($strbooks, new moodle_url('/mod/book/index.php', array('id' => $course->id)));
$PAGE->navbar->add($searchresults);

$PAGE->set_title($searchresults);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

$start = (BOOKMAXRESULTSPERPAGE * $page);

// Process the query.
$query = trim(strip_tags($query));

if (empty($query)) {
    notice(get_string('emptyquery', 'block_search_books'), "../../course/view.php?id=$course->id");
}

// Launch the SQL quey.
$bookresults = search($query, $course, $start, $countentries);

$coursefield = '<input type="hidden" name="courseid" value="'.$course->id.'"/>';
$pagefield = '<input type="hidden" name="page" value="0"/>';
$searchbox = '<input type="text" name="bsquery" size="20" maxlength="255" value="'.s($query).'"/>';
$submitbutton = '<input type="submit" name="submit" value="'.$searchbooks.'"/>';

$content = $coursefield.$pagefield.$searchbox.$submitbutton;

$form = '<form method="get" action="'.$CFG->wwwroot.'/blocks/search_books/search_books.php" name="form" id="form">'.$content.'</form>';

echo '<div style="margin-left: auto; margin-right: auto; width: 100%; text-align: center">' . $form . '</div>';

// Process $bookresults, if present.
$startindex = $start;
$endindex = $start + count($bookresults);

$countresults = $countentries;

// Print results page tip.
$page_bar = glossary_get_paging_bar($countresults, $page, BOOKMAXRESULTSPERPAGE, "search_books.php?bsquery=".urlencode(stripslashes($query))."&amp;courseid=$course->id&amp;");

// Iterate over results.
if (!empty($bookresults)) {
    // Print header
    echo '<p style="text-align: right">'.$strresults.' <b>'.($startindex+1).'</b> - <b>'.$endindex.'</b> '.$ofabout.'<b> '.$countresults.' </b>'.$for.'<b> "'.s($query).'"</b></p>';
    echo $page_bar;
    // Prepare each entry (hilight, footer...)
    echo '<ul>';
    foreach ($bookresults as $entry) {
        $book = $DB->get_record('book', array('id' => $entry->bookid));
        $cm = get_coursemodule_from_instance("book", $book->id, $course->id);

        //To show where each entry belongs to
        $result = "<li><a href=\"$CFG->wwwroot/mod/book/view.php?id=$cm->id\">".format_string($book->name,true)."</a>&nbsp;&raquo;&nbsp;<a href=\"$CFG->wwwroot/mod/book/view.php?id=$cm->id&amp;chapterid=$entry->id\">".format_string($entry->title,true)."</a></li>";
        echo $result;
    }
    echo '</ul>';
    echo $page_bar;
} else {
    echo '<br />';
    echo $OUTPUT->box(get_string("norecordsfound","block_search_books"),'CENTER');
}

echo $OUTPUT->footer();
