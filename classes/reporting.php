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
			SELECT cm2.id, cm2.catid categoryid, cm2.catname categoryname, cm2.catpath categorypath, m.name modulename, count(cm2.id) cnt
				FROM mdl_modules m
				LEFT OUTER JOIN (
					SELECT cc.id catid, cc.name catname, cc.path catpath, cm.id, cm.module
						FROM mdl_course_modules cm
					JOIN mdl_course c
						ON cm.course = c.id
					JOIN mdl_course_categories cc
						ON c.category = cc.id
					GROUP BY c.id, cm.module
				) cm2
					ON cm2.module = m.id
			WHERE exists (SELECT 1 FROM mdl_course_modules WHERE module = m.id)
			GROUP BY m.name
			ORDER BY cm2.catname
SQL;
		$records = $DB->get_records_sql($sql);

		// Stores an array of mappings for category ID -> category name.
		$categories = static::get_categories();

		// Grab a list of all the modules we have.
		$modules = static::get_modules($records);

		// Go through every category, setup the data array for it.
		$data = array();
		foreach ($categories as $catid => $catname) {
			$data[$catid] = array(
				"category" => $catname,
				"modules" => $modules
			);
		}

		// Update all the counts.
		foreach ($records as $record) {
			// Grab a list of categories to update.
			$path = $record->categorypath;
			$paths = explode('/', $path);
			$categories = array_filter($paths, "strlen");

			foreach ($categories as $catid) {
				$data[$catid]["modules"][$record->modulename] += (int)$record->cnt;
			}
		}
		
		return $data;
	}

	/**
	 * Returns a list of category ids and category names.
	 */
	private static function get_categories() {
		global $DB;

		$records = $DB->get_records("course_categories", null, '', $fields='id, name');

		$data = array();
		foreach ($records as $record) {
			$data[$record->id] = $record->name;
		}
		return $data;
	}

	/**
	 * Returns a list of modules in a given dataset
	 */
	private static function get_modules($records) {
		$data = array();
		foreach ($records as $record) {
			if (!isset($data[$record->modulename])) {
				$data[$record->modulename] = 0;
			}
		}
		return $data;
	}
}