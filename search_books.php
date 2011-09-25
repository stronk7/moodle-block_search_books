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

// Search for query terms in course books

include_once ("../../config.php");
include_once($CFG->dirroot.'/mod/glossary/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$query    = required_param('query');
$page     = optional_param('page', 0, PARAM_INT);

require_login($courseid);

DEFINE('MAXRESULTSPERPAGE', 100);  //Limit results per page
DEFINE('MAXPAGEALLOWED', 99);    //Limit number of pages to show

function search( $query, $course, $offset, &$countentries ) {

    global $CFG, $USER;

    /// Some differences in syntax for PostgreSQL
    if ($CFG->dbfamily == "postgres") {
        $LIKE = "ILIKE";   // case-insensitive
        $NOTLIKE = "NOT ILIKE";   // case-insensitive
        $REGEXP = "~*";
        $NOTREGEXP = "!~*";
    } else {
        $LIKE = "LIKE";
        $NOTLIKE = "NOT LIKE";
        $REGEXP = "REGEXP";
        $NOTREGEXP = "NOT REGEXP";
    }

    // Perform the search only in books fulfilling mod/book:read and (visible or moodle/course:viewhiddenactivitie)
    $bookids = array();
    if (! $books = get_all_instances_in_course('book', $course)) {
        notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'book')), "../../course/view.php?id=$course->id");
        die;
    }
    foreach ($books as $book) {
        $cm = get_coursemodule_from_instance("book", $book->id, $course->id);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        if ($cm->visible || has_capability('moodle/course:viewhiddenactivities', $context)) {
            if (has_capability('mod/book:read', $context)) {
                $bookids[] = $book->id;
            }
        }
    }

    // Seach starts
    $titlesearch = "";
    $contentsearch = "";

    $searchterms = explode(" ",$query);

    foreach ($searchterms as $searchterm) {

        if ($titlesearch) {
            $titlesearch .= " AND ";
        }
        if ($contentsearch) {
            $contentsearch .= " AND ";
        }

        if (substr($searchterm,0,1) == "+") {
            $searchterm = substr($searchterm,1);
            $titlesearch .= " bc.title $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $contentsearch .= " bc.content $REGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
        } else if (substr($searchterm,0,1) == "-") {
            $searchterm = substr($searchterm,1);
            $titlesearch .= " bc.title $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
            $contentsearch .= " bc.content $NOTREGEXP '(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)' ";
        } else {
            $titlesearch .= " bc.title $LIKE '%$searchterm%' ";
            $contentsearch .= " bc.content $LIKE '%$searchterm%' ";
        }
    }

    //Add seach conditions in titles and contents
    $where = "AND (( $titlesearch) OR ($contentsearch) ) ";

    $module = get_record('modules', 'name', 'book');

    $sqlselect  = "SELECT DISTINCT bc.*";
    $sqlfrom    = "FROM {$CFG->prefix}book_chapters bc,
                        {$CFG->prefix}book b";
    $sqlwhere   = "WHERE b.course = $course->id AND
                         b.id IN (" . implode($bookids, ', ') . ") AND
                         bc.bookid = b.id AND
                         bc.hidden = 0
                         $where";
    $sqlorderby = "ORDER BY bc.bookid, bc.pagenum";

    $limitfrom = $offset;
    $limitnum = 0;
    if ( $offset >= 0 ) {
        $limitnum = MAXRESULTSPERPAGE;
    }

    $countentries = count_records_sql("select count(*) $sqlfrom $sqlwhere");
    $allentries =   get_records_sql("$sqlselect $sqlfrom $sqlwhere $sqlorderby", $limitfrom, $limitnum);

    return $allentries;
}

//////////////////////////////////////////////////////////
// The main part of this script

$strsearchresults = get_string("searchresults","block_search_books");

if (! $course = get_record("course", "id", $courseid) ) {
    error("That's an invalid course id");
}

if ($course->category) {
    print_header("$course->shortname: $strsearchresults", "$course->fullname",
                 "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->
                 $strsearchresults", "form.query");
} else {
    print_header("$course->shortname: $strsearchresults", "$course->fullname",
                 "$strsearchresults", "form.query");
}

$start = (MAXRESULTSPERPAGE*$page);

//Process the query
$query = trim(strip_tags($query));

//Launch the SQL quey
$glossarydata = search( $query, $course, $start, $countentries);

$searchbooks = get_string('bookssearch', 'block_search_books');
$searchresults = get_string('searchresults', 'block_search_books');
$strresults = get_string('results', 'block_search_books');
$ofabout = get_string('ofabout', 'block_search_books');
$for = get_string('for', 'block_search_books');
$seconds = get_string('seconds', 'block_search_books');

$coursefield = '<input type="hidden" name="courseid" value="'.$course->id.'"/>';
$pagefield = '<input type="hidden" name="page" value="0"/>';
$searchbox = '<input type="text" name="query" size="20" maxlength="255" value="'.s($query).'"/>';
$submitbutton = '<input type="submit" name="submit" value="'.$searchbooks.'"/>';

$content = $coursefield.$pagefield.$searchbox.$submitbutton;

$form = '<form method="get" action="'.$CFG->wwwroot.'/blocks/search_books/search_books.php" name="form" id="form">'.$content.'</form>';

echo '<div style="margin-left: auto; margin-right: auto; width: 100%; text-align: center">' . $form . '</div>';

//Process $glossarydata, if present
$startindex = $start;
$endindex = $start + count($glossarydata);

$countresults = $countentries;

//Print results page tip
$page_bar = glossary_get_paging_bar($countresults, $page, MAXRESULTSPERPAGE, "search_books.php?query=".urlencode(stripslashes($query))."&amp;courseid=$course->id&amp;");

//Iterate over results
if (!empty($glossarydata)) {
    //Print header
    echo '<p style="text-align: right">'.$strresults.' <b>'.($startindex+1).'</b> - <b>'.$endindex.'</b> '.$ofabout.'<b> '.$countresults.' </b>'.$for.'<b> "'.s($query).'"</b>&nbsp;';
    echo $page_bar;
    //Prepare each entry (hilight, footer...)
    echo '<ul>';
    foreach ($glossarydata as $entry) {
        $book = get_record('book', 'id', $entry->bookid);
        $cm = get_coursemodule_from_instance("book", $book->id, $course->id);

        //To show where each entry belongs to
        $result = "<li style=\"margin-left:10%\"><a href=\"$CFG->wwwroot/mod/book/view.php?id=$cm->id\">".format_string($book->name,true)."</a>&nbsp;&raquo;&nbsp;<a href=\"$CFG->wwwroot/mod/book/view.php?id=$cm->id&amp;chapterid=$entry->id\">".format_string($entry->title,true)."</a></li>";
        echo $result;
    }
    echo '</ul>';
    echo $page_bar;
} else {
    echo '<br />';
    print_simple_box(get_string("norecordsfound","block_search_glossaries"),'CENTER');
}

print_footer($course);
