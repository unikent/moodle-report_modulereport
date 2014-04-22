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
 * Classes for the Module Report
 *
 * @package    report_modulereport
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_modulereport;

defined('MOODLE_INTERNAL') || die();

/**
 * Reporting class
 */
class reporting {
	/**
	 * Grab a list of module counts by categories.
	 * 
	 * @global $DB
	 * @return array array (array("category",  "modules" => array("module" => "count", ...)), ...)
	 */
	public static function get_modules_by_category() {
		global $DB;

		$sql = <<<SQL
			SELECT cm.id, cm.module, c.id cid, cm.instance, COUNT(cm.module) mcount, cc.path catpath
				FROM {course_modules} cm
			JOIN {course} c
				ON cm.course = c.id
			JOIN {course_categories} cc
				ON c.category = cc.id
			GROUP BY cm.module, cc.id, c.id
SQL;
		$records = $DB->get_records_sql($sql);

		// Stores an array of mappings for category ID -> category name.
		$categories = static::get_categories();

		// Stores an array of mappings for module ID -> module name.
		$modules = static::get_modules();

		// Placeholder set of 0 counts.
		$module_counts = array_map(function($a) {
			return 0;
		}, $modules);

		// Go through every category, setup the data array for it.
		$data = array();
		foreach ($categories as $catid => $catname) {
			$data[$catid] = array(
				"category" => $catname,
				"modules" => $module_counts
			);
		}

		// Grab a list of IDs to filter out.
		$exclusions = static::get_exclusions_list();

		// Update all the counts.
		foreach ($records as $record) {
			// Grab a list of categories to update.
			$path = $record->catpath;
			$paths = explode('/', $path);
			$categories = array_filter($paths, "strlen");

			foreach ($categories as $catid) {
				// This totals the number of courses using the module, rather than the total
				// number of instances (+= mcount)
				// CR996
				if (!isset($exclusions[$record->module]) || !in_array($record->instance, $exclusions[$record->module])) {
					$data[$catid]["modules"][$record->module]++;
				}
			}
		}

		return $data;
	}

	/**
	 * Returns a list of Module instances within a category
	 * 
	 * @global $DB
	 */
	public static function get_instances_for_category($catid, $moduleid) {
		global $DB;

		$sql = <<<SQL
			SELECT cm.id, c.id as cid, cm.module, cm.instance, c.shortname, COUNT(cm.module) mcount
				FROM {course_modules} cm
			JOIN {course} c
				ON cm.course = c.id
			JOIN {course_categories} cc
				ON c.category = cc.id
			WHERE (cc.path LIKE :cpath1 OR cc.path LIKE :cpath2) AND cm.module = :mid
			GROUP BY cm.module, c.id
SQL;

		$data = $DB->get_records_sql($sql, array(
			"cpath1" => "%/" . $catid . "/%",
			"cpath2" => "%/" . $catid,
			"mid" => $moduleid
		));

		// Grab a list of IDs to filter out.
		$exclusions = static::get_exclusions_list();

		// Filter out certain modules.
		$filtered_data = array();
		foreach ($data as $record) {
			if (!isset($exclusions[$record->module]) || !in_array($record->instance, $exclusions[$record->module])) {
				$filtered_data[] = $record;
			}
		}

		return $filtered_data;
	}

	/**
	 * Return a count of all forum posts for a given course.
	 */
	public static function get_forum_post_count($course_id) {
		global $DB;

		$sql = 'SELECT COUNT(fp.id) as count
				FROM {forum_posts} fp
				INNER JOIN {forum_discussions} fd
					ON fd.id = fp.discussion
				WHERE fd.course=:courseid';

		return $DB->count_records_sql($sql, array(
			"courseid" => $course_id
		));
	}

	/**
	 * Returns a list of category ids and category names.
	 */
	public static function get_categories() {
		global $DB;

		$records = $DB->get_records("course_categories", null, '', 'id, name');

		$data = array();
		foreach ($records as $record) {
			$data[$record->id] = $record->name;
		}

		return $data;
	}

	/**
	 * Returns a list of modules.
	 */
	public static function get_modules() {
		global $DB;

		$records = $DB->get_records("modules", null, '', 'id, name');

		$data = array();
		foreach ($records as $record) {
			$data[$record->id] = $record->name;
		}

		return $data;
	}

	/**
	 * Returns a list of modules we should exclude
	 */
	private static function get_exclusions_list() {
		global $DB;

		// Grab the ID of the forum module.
		$forum = $DB->get_record('modules', array(
			'name' => 'forum'
		), 'id');

		// Grab the ID of the aspire lists module.
		$lists = $DB->get_record('modules', array(
			'name' => 'aspirelists'
		), 'id');

		return array(
			$forum->id => static::filter_list("forum", "News forum"),
			$lists->id => static::filter_list("aspirelists", "Reading list")
		);
	}

	/**
	 * Filter out default modules, this grabs a list of modules that need to be excluded.
	 * 
	 * @return boolean True if this is a default forum, else false.
	 */
	private static function filter_list($table_name, $module_title) {
		global $DB;

		// Grab a list of IDs we can exclude.
		$records = $DB->get_records_sql("SELECT id FROM {".$table_name."} WHERE name = :title", array(
			'title' => $module_title
		));

		// Map to an array.
		$ids = array();
		foreach ($records as $record) {
			$ids[] = $record->id;
		}

		return $ids;
	}
}