<?php
// 데이터베이스 연결 설정
$host = '52.79.41.79';
$dbUser = 'jieun';
$dbPass = 'kii345gh';
$dbName = 'jieunDB';

// DSN (Data Source Name) 설정
$dsn = "mysql:host=$host;dbname=$dbName;charset=utf8";

try {
    // PDO 인스턴스 생성으로 MySQL 데이터베이스에 연결
    $conn = new PDO($dsn, $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
}


?>
