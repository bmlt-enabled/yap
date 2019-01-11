<?php
require_once 'Database.php';

class Session {

    private $db;

    public function __construct() {
        $this->db = new Database;
        session_set_save_handler(
            array($this, "_open"),
            array($this, "_close"),
            array($this, "_read"),
            array($this, "_write"),
            array($this, "_destroy"),
            array($this, "_gc")
        );

        session_start();
    }

    public function _open() {
        if ($this->db) {
            return true;
        }
        return false;
    }

    public function _close() {
        if ($this->db->close()) {
            return true;
        }
        return false;
    }

    public function _read($id) {
        $this->db->query('SELECT `data` FROM `sessions` WHERE id = :id');
        $this->db->bind(':id', $id);
        if($this->db->execute()) {
            $row = $this->db->single();
            if (is_null($row['data'])) {
                return '';
            }
            return $row['data'];
        }
    }

    public function _write($id, $data) {
        $access = time();
        $this->db->query('REPLACE INTO `sessions` VALUES (:id, :access, :data)');
        $this->db->bind(':id', $id);
        $this->db->bind(':access', $access);
        $this->db->bind(':data', $data);
        if ($this->db->execute()) {
            return true;
        }
        return false;
    }

    public function _destroy($id) {
        $this->db->query('DELETE FROM `sessions` WHERE `id` = :id');
        $this->db->bind(':id', $id);
        if ($this->db->execute()) {
            return true;
        }
        return false;
    }

    public function _gc($max) {
        $old = time() - $max;
        $this->db->query('DELETE FROM `sessions` WHERE `access` < :old');
        $this->db->bind(':old', $old);
        if ($this->db->execute()) {
            return true;
        }
        return false;
    }
}
