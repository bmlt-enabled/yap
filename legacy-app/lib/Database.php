<?php
// Define database configuration
define("DB_HOST", $GLOBALS['mysql_hostname']);
define("DB_USER", $GLOBALS['mysql_username']);
define("DB_PASS", $GLOBALS['mysql_password']);
define("DB_NAME", $GLOBALS['mysql_database']);

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class Database
{
    // phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace
    private $host      = DB_HOST;
    private $user      = DB_USER;
    private $pass      = DB_PASS;
    private $dbname    = DB_NAME;
    private $dbh;
    private $error;
    private $stmt;

    public function __construct()
    {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = array(
            PDO::ATTR_PERSISTENT    => true,
            PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
        );
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            throw $e;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function query($query)
    {
        $this->stmt = $this->dbh->prepare($query);
    }

    public function exec($statement)
    {
        $this->dbh->exec($statement);
    }

    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }

        $this->stmt->bindParam($param, $value, $type);
    }

    public function execute()
    {
        return $this->stmt->execute();
    }

    public function resultset()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->dbh->beginTransaction();
    }

    public function endTransaction()
    {
        return $this->dbh->commit();
    }

    public function cancelTransaction()
    {
        return $this->dbh->rollBack();
    }

    public function debugDumpParams()
    {
        return $this->stmt->debugDumpParams();
    }

    public function close()
    {
        $this->dbh = null;
    }
}
