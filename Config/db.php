<?php
// db.php - Database Configuration Class

class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $pdo;
    private static $instance = null;
    
    // ADD THIS: Public connection variable for procedural code
    public $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->dbname = getenv('DB_NAME') ?: 'ecowaste_db';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        $this->charset = getenv('DB_CHARSET') ?: 'utf8mb4';
        
        // Initialize mysqli connection for backward compatibility
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);
        
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        
        $this->conn->set_charset("utf8mb4");
    }

    /**
     * Determine if the app is running in production.
     */
    private function isProduction() {
        $env = getenv('APP_ENV');
        return $env && strtolower($env) === 'production';
    }

    /**
     * Singleton pattern to ensure single database connection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get mysqli connection (for procedural code)
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Establish PDO database connection
     */
    public function connect() {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch(PDOException $e) {
                // Log error instead of displaying it
                error_log("Database Connection Error: " . $e->getMessage());
                
                // For production, use a generic error message
                if ($this->isProduction()) {
                    throw new Exception("Database connection failed. Please try again later.");
                } else {
                    throw new Exception("Database Connection Error: " . $e->getMessage());
                }
            }
        }
        
        return $this->pdo;
    }

    /**
     * Execute query (mysqli style)
     */
    public function query($sql) {
        return $this->conn->query($sql);
    }

    /**
     * Prepare statement (mysqli style)
     */
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }

    /**
     * Escape string
     */
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }

    // ... rest of your existing methods remain the same ...

    /**
     * Check if user exists
     */
    public function userExists($email) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}

// Create global database instance for backward compatibility
$database = Database::getInstance();
$conn = $database->getConnection(); // This is the mysqli connection

?>