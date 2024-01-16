<?php

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require 'db_connect.php'; // 데이터베이스 연결

$email = $_POST['email'];// 클라이언트로부터 받은 이메일

//1. 인증번호 생성
$verificationCode = rand(100000, 999999); // 6자리 랜덤 숫자 생성

//2. db에 이메일, 인증번호, 생성날짜를 저장함
$sql = "INSERT INTO userAuth (email, verification_code, created_at) VALUES (::email, :code, NOW())";
$stmt = $conn-> prepare($sql);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':code', $verificationCode, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll();

// 3. 이메일 발송





if (count($result) > 0) {
    echo json_encode(true); // 인증번호 전송 성공
} else {
    echo json_encode(false); // 인증번호 전송 실패
}

?>