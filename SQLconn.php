<?php
class PDOConnect 
{
    private static $instance = null;
    private $conn;
    private $ServerName = '127.0.0.1';
    private $UID = 'root';
    private $PWD = 'VGVzdDEyMw=='; // Heslo by mělo být dekódováno pouze pokud je to skutečně potřeba
    private $Db;
    private $charset = 'utf8mb4';

    private function __construct($Db)    
    {
        $this->Db = $Db; // Nastavte název databáze
        $maxAttempts = 3; // Test cycle
        $retryDelay = 5; // Waiting time 
    
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                set_time_limit(3600);
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Chybový režim: výjimky
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Výchozí režim načítání: asociativní pole
                    PDO::ATTR_EMULATE_PREPARES   => false,                  // Deaktivace emulovaných příprav
                ]; 

                $this->conn = new PDO(
                    "mysql:host={$this->ServerName};dbname={$this->Db};charset={$this->charset}",
                    $this->UID,
                    base64_decode($this->PWD),
                    $options
                );
                break;
            } catch (PDOException $e) {
                if ($attempt < $maxAttempts) {
                    sleep($retryDelay);
                } else {
                    echo "Connection failed after $maxAttempts attempts: " . $e->getMessage();
                    echo "ServerName - {$this->ServerName}";
                    echo "Username - {$this->UID}";
                    echo "Charset - {$this->charset}";
                    echo "Db - {$this->Db}";
                }
            }
        }
    }

/******************************************************************************************************************************************************************************/
    public static function getInstance($Db)
    {
        if (!self::$instance) {
            self::$instance = new PDOConnect($Db);
        }
        return self::$instance;
    }

/******************************************************************************************************************************************************************************/
    public function select($query, $params = array()) 
    {
        $maxAttempts = 3; // Test cycles
        $retryDelay = 5; // Waiting time 
    
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $stmt = $this->conn->prepare($query);
                $stmt->execute($params);
                $result = array(
                    'rows'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
                    'count' => $stmt->rowCount()
                );
                return $result;
            } 
            catch (PDOException $e) {
                if ($attempt < $maxAttempts) {
                    sleep($retryDelay);
                } else {
                    echo "Error SQL Select: " . $e->getMessage();
                }
            }
        }
    }

/******************************************************************************************************************************************************************************/
    public function insert($table, $data) 
    {
        $maxAttempts = 3; // Test cycles
        $retryDelay = 5; // Waiting time 
    
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $columns = implode(',', array_keys($data));
                $values = ':' . implode(',:', array_keys($data));      
                $query = "INSERT INTO $table ($columns) VALUES ($values)";        

                $stmt = $this->conn->prepare($query);

                foreach ($data as $key => $value) {
                    $stmt->bindValue(":$key", $value);
                }

                $stmt->execute();        
                return $stmt->rowCount();
            } 
            catch (PDOException $e) {
                if ($attempt < $maxAttempts) {
                    sleep($retryDelay);
                } else {
                    echo "Error SQL Insert: " . $e->getMessage();
                }
            }
        }
    }

/******************************************************************************************************************************************************************************/
    public function update($query, $params = array()) 
    {
        $maxAttempts = 3; // Test cycles
        $retryDelay = 5; // Waiting time 
    
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $stmt = $this->conn->prepare($query);
                $stmt->execute($params);
                return $stmt->rowCount();
            } 
            catch(PDOException $e) {
                if ($attempt < $maxAttempts) {
                    sleep($retryDelay);
                } else {
                    echo "Error SQL Update: " . $e->getMessage();
                }
            }
        }
    }

/******************************************************************************************************************************************************************************/
    public function tempTB($sql,$tableName)
    {
        $maxAttempts = 3; // Test cycles
        $retryDelay = 5; // Waiting time 
    
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $checkTableExists = "CREATE TABLE IF NOT EXISTS $tableName ($sql)";
                $this->conn->exec($checkTableExists);
                return true;
            } 
            catch (PDOException $e) {
                if ($attempt < $maxAttempts) {
                    sleep($retryDelay);
                } else {
                    echo "Chyba při vytváření dočasné tabulky: " . $e->getMessage();
                }
            }
        }
    }

/******************************************************************************************************************************************************************************/
    public function execute($query, $params = array()) 
    {
        $maxAttempts = 3; // Test cycles
        $retryDelay = 5; // Waiting time 
    
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $stmt = $this->conn->prepare($query);
                $stmt->execute($params);
                return array(
                    'rows'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
                    'count' => $stmt->rowCount()
                );
            } 
            catch(PDOException $e) {
                if ($attempt < $maxAttempts) {
                    sleep($retryDelay);
                } else {
                    echo "Error SQL Execute: " . $e->getMessage();
                }
            }
        }
    }
}
?>
