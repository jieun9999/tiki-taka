<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결

$userId = $_GET['userId']; // 클라이언트에서 받은 userId

// 유저 프로필 가져오기
$sql = "SELECT * FROM userProfile WHERE user_id = :userId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->execute();
$userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

// 파트너 프로필 가져오기
$sql2 = "SELECT partner_id FROM userAuth WHERE user_id = :userId";
$stmt = $conn->prepare($sql2);
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->execute();
$partnerId = $stmt->fetchColumn();

$partnerProfile = null;
if ($partnerId) {
    $sql3 = "SELECT * FROM userProfile WHERE user_id = :partnerId";
    $stmt = $conn->prepare($sql3);
    $stmt->bindParam(':partnerId', $partnerId, PDO::PARAM_INT);
    $stmt->execute();
    $partnerProfile = $stmt->fetch(PDO::FETCH_ASSOC);
}


// 결과 응답
//유저 프로필(UserProfile)과 파트너 프로필(PartnerProfile) 데이터를 하나의 JSON 응답으로 클라이언트에 보냄
header('Content-Type: application/json');
if ($userProfile || $partnerProfile) {
    echo json_encode([
        "userProfile" => $userProfile,
        "partnerProfile" => $partnerProfile
    ]);
} else {
    echo json_encode(["error" => "No data found for the given user ID"]);
}

?>