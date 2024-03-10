<?php

class MoodleGrade {
    protected $db;
    protected $main_table;
    protected $items_table;
    protected $grades_table;
    protected $id_column;
    protected $update_grades_function;
    
    
    /**
     * コンストラクタ。
     * @param string $main_table モジュールのメインテーブル名
     * @param string $items_table itemsテーブル名
     * @param string $grades_table gradesテーブル名
     * @param string $id_column idカラム名
     * @param function $update_grades_function 成績更新関数
     */
    function __construct($main_table, $items_table, $grades_table, $id_column, $update_grades_function) {
        global $DB;
        $this->db = $DB;
        $this->main_table = $main_table;
        $this->items_table = $items_table;
        $this->grades_table = $grades_table;
        $this->id_column = $id_column;
        $this->update_grades_function = $update_grades_function;
    }
    
    
    /**
     * 成績を書き込む。
     * @param unknown_type $ctx
     * @param unknown_type $activity_id
     * @param unknown_type $counter
     * @param unknown_type $type
     * @param unknown_type $grade
     */
    public function writeGrade($ctx, $activity_id, $counter, $type, $grade)
    {
        $elecoaid = $ctx->getCid();
        $userid = $ctx->getUid();
        $attempt = $ctx->getAttemptCount();
        $item = $this->db->get_record($this->items_table, array($this->id_column => $elecoaid, 'identifier' => $activity_id));
        
        if($item) {
            
            $this->db->delete_records($this->grades_table, array($this->id_column => $elecoaid, 'itemid'=>$item->id, 'userid'=>$userid, 'attempt'=>$attempt, 'counter'=>$counter));
            
            if (!is_null($counter)) {
                $data = new stdClass();
                $data->{$this->id_column} = $elecoaid;
                $data->parentid = $item->id;
                $data->itemid = $item->id;
                $data->userid = $userid;
                $data->attempt = $attempt;
                $data->counter = $counter;
                $data->completion = $this->getCompletionValue($grade->completionStatus);
                $data->success = $this->getSuccessValue($grade->successStatus);
                $data->score = $this->getScoreValue($grade->scaledScore);
                $data->lessontime = time();
                $data->lessonperiod = $grade->sessionTime; //$grade->totalTime - $totalperiod;
                $data->totalperiod = $grade->totalTime;
                $this->db->insert_record($this->grades_table, $data);
            } else {
                $lessontime = null;
                $lessonperiod = null;
                $totalperiod = null;

                if ($type == 'LEAF' or $type == 'BLOCK') {
                    // lessontime, lessonperiod
                    if ($r = $this->db->get_record($this->grades_table, array('parentid' => $item->id, 'itemid' => $item->id, 'userid' => $userid, 'attempt' => $attempt), 'MAX(counter) AS maxcounter')) {
                        if ($r = $this->db->get_record($this->grades_table, array('parentid' => $item->id, 'itemid' => $item->id, 'userid' => $userid, 'attempt' => $attempt, 'counter' => $r->maxcounter))) {
                            $lessontime = $r->lessontime;
                            $lessonperiod = $r->lessonperiod;
                        }
                    }
                    // totalperiod
                    if ($r = $this->db->get_record($this->grades_table, array('parentid' => $item->id, 'itemid' => $item->id, 'userid' => $userid, 'attempt' => $attempt), 'SUM(totalperiod) AS sumtotalperiod')) {
                        $totalperiod = $r->sumtotalperiod;
                    }
                } else {
                    // lessontime
                    if ($r = $this->db->get_record($this->grades_table, array('parentid' => $item->id, 'userid' => $userid, 'attempt' => $attempt), 'MAX(lessontime) AS maxlessontime')) {
                        $lessontime = $r->maxlessontime;
                    }
                    // totalperiod, lessonperiod
                    if ($r = $this->db->get_record($this->grades_table, array('parentid' => $item->id, 'userid' => $userid, 'attempt' => $attempt), 'SUM(totalperiod) AS sumtotalperiod')) {
                        $totalperiod = $r->sumtotalperiod;
                        $lessonperiod = $totalperiod;
                    }
                }

                $data = new stdClass();
                $data->{$this->id_column} = $elecoaid;
                $data->parentid = $item->parentid;
                $data->itemid = $item->id;
                $data->userid = $userid;
                $data->attempt = $attempt;
                $data->counter = $counter;
                $data->completion = $this->getCompletionValue($grade->completionStatus);
                $data->success = $this->getSuccessValue($grade->successStatus);
                $data->score = $this->getScoreValue($grade->scaledScore);
                $data->lessontime = $lessontime;
                $data->lessonperiod = $lessonperiod;
                $data->totalperiod = $totalperiod;
                $this->db->insert_record($this->grades_table, $data);
            }

            if ($type == 'ROOT') {
                $elecoa = $this->db->get_record($this->main_table, array('id' => $elecoaid));
                call_user_func($this->update_grades_function, $elecoa, $userid, TRUE);
            }

        }
    }

    public function getCompletionValue($status)
    {
        if ($status=='completed') {
            return 0;
        } else if ($status=='incomplete') {
            return 1;
        } else if ($status=='not_attempted') {
            return 2;
        } else {
            return 3; // unknown
        }
    }
    public function getSuccessValue($status)
    {
        if ($status=='satisfied') {
            return 0;
        } else if ($status=='not satisfied') {
            return 1;
        } else {
            return 2; // unknown
        }
    }
    public function getScoreValue($score)
    {
        if ( $score === '' ) {
            return null;
        } else {
            return $score;
        }
    }
}
