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
 * Edit grading form in for a particular instance of workshop
 *
 * @package    mod_workshopplus
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$cmid       = required_param('cmid', PARAM_INT);

$cm         = get_coursemodule_from_id('workshopplus', $cmid, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, false, $cm);
require_capability('mod/workshop:editdimensions', $PAGE->context);

$workshop   = $DB->get_record('workshopplus', array('id' => $cm->instance), '*', MUST_EXIST);
$workshop   = new workshopplus($workshop, $cm, $course);

// todo: check if there already is some assessment done and do not allowed the change of the form
// once somebody already used it to assess

$PAGE->set_url($workshop->editform_url());
$PAGE->set_title($workshop->name);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('editingassessmentform', 'workshopplus'));

// load the grading strategy logic
$strategy = $workshop->grading_strategy_instance();

// load the form to edit the grading strategy dimensions
$mform = $strategy->get_edit_strategy_form($PAGE->url);

if ($mform->is_cancelled()) {
    redirect($workshop->view_url());
} else if ($data = $mform->get_data()) {
    if (($data->workshopid != $workshop->id) or ($data->strategy != $workshop->strategy)) {
        // this may happen if someone changes the workshop setting while the user had the
        // editing form opened
        throw new invalid_parameter_exception('Invalid workshop ID or the grading strategy has changed.');
    }
    $strategy->save_edit_strategy_form($data);
    if (isset($data->saveandclose)) {
        redirect($workshop->view_url());
    } else if (isset($data->saveandpreview)) {
        redirect($workshop->previewform_url());
    } else {
        // save and continue - redirect to self to prevent data being re-posted by pressing "Reload"
        redirect($PAGE->url);
    }
}

// Output starts here

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($workshop->name));
echo $OUTPUT->heading(get_string('pluginname', 'workshopform_' . $workshop->strategy), 3);

$mform->display();

echo $OUTPUT->footer();
