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
				if (static::filter_default("forum", "News forum", $record->module, $record->instance) &&
					static::filter_default("aspirelists", "Reading list", $record->module, $record->instance)) {
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

		$filtered_data = array();
		foreach ($data as $record) {
			if (static::filter_default("forum", "News forum", $record->module, $record->instance) &&
				static::filter_default("aspirelists", "Reading list", $record->module, $record->instance)) {
				$filtered_data[] = $record;
			}
		}

		return $filtered_data;
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
	 * Filter out default modules
	 * 
	 * @return boolean True if this is a default forum, else false.
	 */
	private static function filter_default($module_name, $module_title, $module, $moduleid) {
		global $DB;

		static $mod_type = array();
		if (!isset($mod_type[$module_name])) {
			$mod = $DB->get_record('modules', array(
				'name' => $module_name
			), 'id');
			$mod_type[$module_name] = $mod->id;
		}

		// Is $module of this type?
		if ($module !== $mod_type[$module_name]) {
			return true;
		}

		// Is is!
		$record = $DB->get_record($module_name, array(
			'id' => $moduleid
		));

		return !$record || $record->name != $module_title;
	}
}