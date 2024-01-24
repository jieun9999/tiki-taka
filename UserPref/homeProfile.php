<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결

$userId = $_GET['userId']; // 클라이언트에서 받은 userId

// userProfile 테이블에서 필요한 정보를 선택하는 SQL 쿼리
$sql = "SELECT * FROM userProfile WHERE user_id = :userId";


// SQL 쿼리 준비
$stmt = $conn->prepare($sql);

// 바인딩
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

// SQL 쿼리 실행
$stmt->execute();

// 결과 가져오기
$result = $stmt->fetch(PDO::FETCH_ASSOC);


// 결과를 클라이언트에 응답으로 보내기
if ($result) {
    // HTTP 헤더 설정
    header('Content-Type: application/json');

    // 데이터를 JSON 형식으로 인코딩하여 출력
    echo json_encode($result);

} else {
    // HTTP 헤더 설정
    header('Content-Type: application/json');

    // 에러 메시지를 JSON 형식으로 보내기
    echo json_encode(array("error" => "No data found for the given user ID"));

}

?>