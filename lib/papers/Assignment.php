<?php
/**
 * Created by PhpStorm.
 * User: Caleb Falcione
 * Date: 5/9/2017
 * Time: 2:43 PM
 */

namespace Papers;


class Assignment
{

    public function getID() {
        return $this->id;
    }

    public function getEncoding() {
        return $this->encoding;
    }

    public function getDateCreated () {
        return $this->date_created;
    }

    public function getStatus () {
        return $this->status;
    }

    public function getCompletion() {
        return $this->completion;
    }

    private $id;
    private $encoding;
    private $date_created;
    private $status;
    private $completion;

    public function __construct($assignment){
        try {
            $this->id = $assignment['_key'];
            $this->encoding = new Encoding($assignment['encoding'], $this->id);
            $this->date_created = $assignment['date_created'];
            $this->status = $assignment['status'];
            $this->completion = $assignment['completion'];
        } catch (Exception $e) {
            //TODO
        }
    }
}