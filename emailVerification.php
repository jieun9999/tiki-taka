<?php

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require 'db_connect.php'; // 데이터베이스 연결

$email = $_GET['email'];// 클라이언트로부터 받은 이메일

//$conn을 사용하여 데이터베이스 연결을 참조하고
// PDO에 적합한 방식으로 결과를 처리

$sql = "SELECT email FROM userAuth WHERE email = :email";
$stmt = $conn-> prepare($sql);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetchAll();


if (count($result) > 0) {
    echo json_encode(true); // 이미 가입된 이메일
} else {
    echo json_encode(false); // 가입 가능한 이메일
}

?>