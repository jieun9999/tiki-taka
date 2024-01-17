<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require 'db_connect.php'; // 데이터베이스 연결

$email = $_POST['email']; // 유저로부터 받은 이메일
$password = $_POST['password'];// 클라이언트로부터 받은 비번

// 비밀번호 해싱
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

//$conn을 사용하여 데이터베이스 연결을 참조하고
// PDO에 적합한 방식으로 결과를 처리

//password 필드가 현재 null이거나 비어 있는 상태라 하더라도, UPDATE 쿼리를 사용하여 그 필드를 새로운 값으로 업데이트하는 것은 완전히 가능
// UPDATE 쿼리는 지정된 조건에 맞는 레코드를 찾아 해당 레코드의 하나 또는 여러 필드의 값을 변경
// UPDATE 쿼리 준비
$sql = "UPDATE userAuth SET password = :password WHERE email = :email";
$stmt = $conn-> prepare($sql);

// 파라미터 바인딩
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

// 쿼리 실행
$result = $stmt->execute();

if ($result) {
    // rowCount()를 사용하여 업데이트된 행의 수 확인
    if ($stmt->rowCount() > 0) {
        echo json_encode(true); // 저장 성공
    } else {
        echo json_encode(false); // 저장 실패 (이메일이 존재하지 않을 수 있음)
    }
} else {
    // 쿼리 실행 실패
    echo json_encode(false); // 저장 실패
}

?>