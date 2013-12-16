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

        $content->data=$this->get_root_node();

        return $content;
    }

    private function get_root_node() {
        global $DB;
        
        $sql = <<< SQLDATA
SELECT cc.id,cc.name, count(cm.id) totalModuleCount 
FROM mdl_course_categories cc
LEFT OUTER JOIN mdl_course_categories cc2
ON cc.depth<=cc2.depth and CONCAT(cc2.path,'/') like CONCAT (cc.path,'/%')
LEFT OUTER JOIN mdl_course c
ON c.category = cc2.id
LEFT OUTER JOIN mdl_course_modules cm
ON cm.course=c.id
where cc.depth=1 
and cc.id <>1
group by cc.id;
SQLDATA;

        $params = array();
        $data = $DB->get_records_sql($sql,$params);
        $array=array();
        foreach ($data as $key=>$value) {
            $value->moduleCount =  $this->get_modules_node($value->id); 
            $value->totalModuleCount =  $value->totalmodulecount; 
            unset($value->totalmodulecount); 
            $value->children =  $this->get_children_node($value->id); 
            $value->courses =  $this->get_courses_node($value->id); 
            array_push($array,$value);
        }
        return $array;
    }
    
    private function get_children_node($id) {
        global $DB;
        
        $sql = <<< SQLDATA
SELECT cc2.id,cc2.name, count(cm.id) totalModuleCount 
FROM mdl_course_categories cc
JOIN mdl_course_categories cc2
ON cc.depth=cc2.depth-1 and CONCAT(cc2.path,'/') like CONCAT (cc.path,'/%')
LEFT OUTER JOIN mdl_course_categories cc3
ON cc2.depth<=cc3.depth and cc3.path like CONCAT (cc2.path,'%')
LEFT OUTER JOIN mdl_course c
ON c.category = cc3.id
LEFT OUTER JOIN mdl_course_modules cm
ON cm.course=c.id
where cc.id=:id
group by cc2.id;
SQLDATA;

        $params = array('id' => $id);
        $data = $DB->get_records_sql($sql, $params);

        $array=array();
        foreach ($data as $key=>$value) {
            $value->moduleCount =  $this->get_modules_node($value->id); 
            $value->totalModuleCount =  $value->totalmodulecount; 
            unset($value->totalmodulecount); 
            $value->children =  $this->get_children_node($value->id); 
            $value->courses =  $this->get_courses_node($value->id); 
            array_push($array,$value);
        }

        return $array;

}
    
    private function get_courses_node($id) {
        global $DB;
        
        $sql = <<< SQLDATA
SELECT c.id, c.shortname name, count(cm.id) totalModuleCount 
FROM  mdl_course c
JOIN mdl_course_modules cm
ON cm.course=c.id
JOIN mdl_modules m
ON cm.module = m.id
JOIN mdl_course_categories cc
ON c.category = cc.id
AND cc.id=:id
group by c.id
ORDER BY c.shortname;
SQLDATA;

        $params = array('id' => $id);
        $data = $DB->get_records_sql($sql, $params);

//var_dump(array('sql'=>$sql,'params'=>$params,'data'=>$data,'tag'=>'1'));
        $array=array();
        foreach ($data as $key=>$value) {
            $value->moduleCount =  $this->get_modules_node($value->id); 
            $value->totalModuleCount =  $value->totalmodulecount; 
            unset($value->totalmodulecount); 
            array_push($array,$value);
        }
        return $array;

    }

    private function get_modules_node($id) {
        global $DB;
        
        $sql = <<< SQLDATA
SELECT m.name, count(cm2.id) cnt
FROM mdl_modules m
LEFT OUTER JOIN (
SELECT cm.id, cm.module
FROM mdl_course_modules cm
JOIN mdl_course c
ON cm.course=c.id
JOIN mdl_course_categories cc2
ON c.category = cc2.id
JOIN mdl_course_categories cc
ON cc.depth<=cc2.depth and CONCAT(cc2.path,'/') like CONCAT (cc.path,'/%')
AND cc.id=:id) cm2
ON cm2.module = m.id
WHERE exists (select 1 FROM mdl_course_modules WHERE module = m.id)
group by m.name
ORDER BY m.name
SQLDATA;

        $params = array('id' => $id);
        $data = $DB->get_records_sql($sql, $params);

//var_dump(array('sql'=>$sql,'params'=>$params,'data'=>$data,'tag'=>'2'));
        $array=array();
        foreach ($data as $key=>$value) {
            $array = array_merge($array,array($value->name=>$value->cnt));
        }
        return $array;
    }

    private function get_course_node($id) {
        global $DB;
        
        $sql = <<< SQLDATA
SELECT m.name, count(cm2.id) cnt 
FROM mdl_modules m
LEFT OUTER JOIN (
SELECT cm.id, cm.module
FROM mdl_course_modules cm
JOIN mdl_course c
ON cm.course=c.id
AND c.id=:id) cm2
ON cm2.module = m.id
WHERE exists (select 1 FROM mdl_course_modules WHERE module = m.id)
group by m.name
ORDER BY m.name;
SQLDATA;

        $params = array('id' => $id);
        $data = $DB->get_records_sql($sql, $params);

//var_dump(array('sql'=>$sql,'params'=>$params,'data'=>$data,'tag'=>'2'));
        $array=array();
        foreach ($data as $key=>$value) {
            $array = array_merge($array,array($value->name=>$value->cnt));
        }
        return $array;
    }

}