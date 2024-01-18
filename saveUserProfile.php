<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require 'db_connect.php'; // 데이터베이스 연결

//

$userId = $_POST['userId']; // 유저식별
$userProfileJson = $_POST['userProfile'];// 유저 데이터 모음 (JSON 문자열)
// JSON 문자열을 PHP 객체로 변환
$userProfile = json_decode($userProfileJson);

// 배열로 변환
$userProfileArray = json_decode($userProfileJson, true);
// 이 경우 배열 접근 방식을 사용
$name = $userProfileArray['name'];


// 업데이트 쿼리

// 바인딩

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo json_encode(["success" => true, "message" => "저장 성공!"]);
} else {
    echo json_encode(["success" => false, "message" => "저장 실패"]);
}

?>