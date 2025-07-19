<?php
function getDBConnection() {
    try {
        
        $host = 'localhost';
        $dbname = 's22800098_vela'; 
        $username = 's22800098_vela';
        $password = 'wathafenvela';  
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        
        return new PDO($dsn, $username, $password, $options);
    } catch(PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        throw $e;
    }
}
?>