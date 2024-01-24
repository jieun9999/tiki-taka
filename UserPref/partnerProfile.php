<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결

$userId = $_GET['userId']; // 클라이언트에서 받은 userId

// partner_id 가져오기
$sql = "SELECT partner_id FROM userAuth WHERE user_id = :userId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->execute();
$partnerId = $stmt->fetchColumn(); // partner_id 가져오기

if ($partnerId) {
    // partner_id가 존재하는 경우 userProfile 가져오기
    $sql2 = "SELECT * FROM userProfile WHERE user_id = :partnerId";
    $stmt = $conn->prepare($sql2);
    $stmt->bindParam(':partnerId', $partnerId, PDO::PARAM_INT);
    $stmt->execute();

    $partnerProfile = $stmt->fetch(PDO::FETCH_ASSOC);
    // 결과를 로그로 기록
    error_log("User profile data: " . json_encode($partnerProfile));

    if($partnerProfile){

    // $userProfile 변수에 userProfile 데이터가 들어 있음
    // HTTP 헤더 설정
    header('Content-Type: application/json');
    // 데이터를 JSON 형식으로 인코딩하여 출력
    echo json_encode($partnerProfile);

    }else{
    // partner_id가 존재하지 않는 경우 처리
    http_response_code(404); // HTTP 상태 코드 404를 설정
    header('Content-Type: application/json');
    echo json_encode(array("error" => "No data found for the partner profile"));

    }

} else {
    // partner_id가 존재하지 않는 경우 처리
    http_response_code(404); // HTTP 상태 코드 404를 설정
    header('Content-Type: application/json');
    echo json_encode(array("error" => "No data found for the given user ID"));

}

?>