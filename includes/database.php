<?php
/**
 * データベース接続クラス
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            // MySQL/MariaDB接続
            $host = DB_HOST;
            $dbname = DB_NAME;
            $username = DB_USER;
            $password = DB_PASS;
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $this->pdo = new PDO($dsn, $username, $password);
            
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            if (defined('DEBUG') && DEBUG) {
                throw new Exception("データベース接続エラー: " . $e->getMessage());
            } else {
                throw new Exception("データベース接続に失敗しました");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * SELECT文を実行して複数行を取得
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            if (DEBUG) {
                throw new Exception("SELECT エラー: " . $e->getMessage() . " SQL: " . $sql);
            } else {
                throw new Exception("データ取得に失敗しました");
            }
        }
    }
    
    /**
     * SELECT文を実行して1行を取得
     */
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            if (DEBUG) {
                throw new Exception("SELECT エラー: " . $e->getMessage() . " SQL: " . $sql);
            } else {
                throw new Exception("データ取得に失敗しました");
            }
        }
    }
    
    /**
     * INSERT文を実行
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            if (DEBUG) {
                throw new Exception("INSERT エラー: " . $e->getMessage() . " SQL: " . $sql);
            } else {
                throw new Exception("データ挿入に失敗しました");
            }
        }
    }
    
    /**
     * UPDATE文を実行
     */
    public function update($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            if (DEBUG) {
                throw new Exception("UPDATE エラー: " . $e->getMessage() . " SQL: " . $sql);
            } else {
                throw new Exception("データ更新に失敗しました");
            }
        }
    }
    
    /**
     * DELETE文を実行
     */
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            if (DEBUG) {
                throw new Exception("DELETE エラー: " . $e->getMessage() . " SQL: " . $sql);
            } else {
                throw new Exception("データ削除に失敗しました");
            }
        }
    }
    
    /**
     * トランザクション開始
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * コミット
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * ロールバック
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * 任意のSQL文を実行（DDL用）
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            if (DEBUG) {
                throw new Exception("SQL実行エラー: " . $e->getMessage() . " SQL: " . $sql);
            } else {
                throw new Exception("SQL実行に失敗しました");
            }
        }
    }
}
