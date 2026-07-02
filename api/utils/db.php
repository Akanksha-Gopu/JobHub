<?php

require_once __DIR__ . "/pdo.php";

class DB {
    public $query = '';
    private  $pdo = null;
    private $stmt = null;

    function __construct()
    {
        $this->pdo = getPDO();   
    }

    public function query($query) {
        $this->query = $query;
    }

    public function prepare() {
        $this->stmt = $this->pdo->prepare($this->query);
    }

    public function execute($params) {
        return $this->stmt->execute($params);
    }

    public function first($params = []) {
        $this->prepare();
        $this->execute($params);
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function get($params = []) {
        $this->prepare();
        $this->execute($params);
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($params = []) {
        $this->prepare();
        return $this->execute($params);
    }

    public function update($params = []) {
        $this->prepare();
        return $this->execute($params);
    }

    public function delete($params = []) {
        $this->prepare();
        return $this->execute($params);
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}