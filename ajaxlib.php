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
 * ajax for module report
 *
 * @package    modulereport
 */

/**
 * Module Report AJAX class
 */
class modulereport_ajax {

    private $debugmode;
    private $isconfigured;
    private $warning;
    private $error;

    public function __construct() {
        global $CFG;
        
    }

    public function get_content() {
        global $DB, $CFG;
        
        // Setup content
        $content = new stdClass();
        $content->data = array();

        $content->data = $this->get_root_node();

        return $content;
    }

    public function get_root_node() {
        global $DB;
        
        $sql = <<< SQL
            SELECT cc.id,cc.name, count(cm.id) totalModuleCount 
                FROM {course_categories} cc
            LEFT OUTER JOIN {course_categories} cc2
                ON cc.depth <= cc2.depth AND CONCAT(cc2.path,'/') LIKE CONCAT (cc.path,'/%')
            LEFT OUTER JOIN {course} c
                ON c.category = cc2.id
            LEFT OUTER JOIN {course_modules} cm
                ON cm.course = c.id
            WHERE cc.depth = 1 
                AND cc.id <>1
                AND cc.name <> "Removed"
            GROUP BY cc.id;
SQL;

        $data = $DB->get_records_sql($sql, array());

        $array = array();
        foreach ($data as $key => $value) {
            $value->moduleCount = $this->get_modules_node($value->id); 
            $value->totalModuleCount = $value->totalmodulecount; 
            unset($value->totalmodulecount); 
            $value->children = $this->get_children_node($value->id); 
            $value->courses = $this->get_courses_node($value->id); 
            array_push($array, $value);
        }
        return $array;
    }
    
    public function get_children_node($id) {
        global $DB;
        
        $sql = <<< SQL
            SELECT cc2.id, cc2.name, count(cm.id) totalModuleCount 
                FROM {course_categories} cc
                JOIN {course_categories} cc2
                    ON cc.depth = cc2.depth-1 AND CONCAT(cc2.path,'/') LIKE CONCAT (cc.path,'/%')
                LEFT OUTER JOIN {course_categories} cc3
                    ON cc2.depth <= cc3.depth AND cc3.path LIKE CONCAT (cc2.path,'%')
                LEFT OUTER JOIN {course} c
                    ON c.category = cc3.id
                LEFT OUTER JOIN {course_modules} cm
                    ON cm.course = c.id
            WHERE cc.id = :id
            GROUP BY cc2.id;
SQL;

        $data = $DB->get_records_sql($sql, array(
            'id' => $id
        ));

        $array = array();
        foreach ($data as $key => $value) {
            $value->moduleCount = $this->get_modules_node($value->id); 
            $value->totalModuleCount = $value->totalmodulecount; 
            unset($value->totalmodulecount); 
            $value->children = $this->get_children_node($value->id); 
            $value->courses = $this->get_courses_node($value->id); 
            array_push($array, $value);
        }

        return $array;

}
    
    public function get_courses_node($id) {
        global $DB;
        
        $sql = <<< SQL
            SELECT c.id, c.shortname name, count(cm.id) totalModuleCount 
                FROM  {course} c
                JOIN {course_modules} cm
                    ON cm.course = c.id
                JOIN {modules} m
                    ON cm.module = m.id
                JOIN {course_categories} cc
                    ON c.category = cc.id
                    AND cc.id = :id
            GROUP BY c.id
            ORDER BY c.shortname;
SQL;

        $data = $DB->get_records_sql($sql, array(
            'id' => $id
        ));

        $array = array();
        foreach ($data as $key => $value) {
            $value->moduleCount = $this->get_modules_node($value->id); 
            $value->totalModuleCount = $value->totalmodulecount; 
            unset($value->totalmodulecount); 
            array_push($array, $value);
        }
        return $array;

    }

    public function get_modules_node($id) {
        global $DB;
        
        $sql = <<< SQL
            SELECT m.name, count(cm2.id) cnt
                FROM {modules} m
                LEFT OUTER JOIN (
                    SELECT cm.id, cm.module
                        FROM {course_modules} cm
                    JOIN {course} c
                        ON cm.course = c.id
                    JOIN {course_categories} cc2
                        ON c.category = cc2.id
                    JOIN {course_categories} cc
                        ON cc.depth <= cc2.depth
                        AND CONCAT(cc2.path,'/') LIKE CONCAT (cc.path,'/%')
                        AND cc.id = :id
                    GROUP BY c.id, cm.module
                ) cm2
                    ON cm2.module = m.id
            WHERE exists (SELECT 1 FROM {course_modules} WHERE module = m.id)
            GROUP BY m.name
            ORDER BY m.name
SQL;

        $data = $DB->get_records_sql($sql, array(
            'id' => $id
        ));

        $array = array();
        foreach ($data as $key => $value) {
            $array = array_merge($array, array(
                $value->name => $value->cnt
            ));
        }
        return $array;
    }

    public function get_course_node($id) {
        global $DB;
        
        $sql = <<< SQL
            SELECT m.name, count(cm2.id) cnt 
                FROM {modules} m
            LEFT OUTER JOIN (
                SELECT cm.id, cm.module
                    FROM {course_modules} cm
                    JOIN {course} c
                        ON cm.course = c.id
                        AND c.id = :id
            ) cm2
                ON cm2.module = m.id
            WHERE exists (SELECT 1 FROM {course_modules} WHERE module = m.id)
            GROUP BY m.name
            ORDER BY m.name;
SQL;

        $data = $DB->get_records_sql($sql, array(
            'id' => $id
        ));

        $array = array();
        foreach ($data as $key => $value) {
            $array = array_merge($array, array(
                $value->name => $value->cnt
            ));
        }
        return $array;
    }

}