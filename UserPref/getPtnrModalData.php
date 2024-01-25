<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결

$userId = $_GET['userId']; // 클라이언트에서 받은 userId

/// 파트너 프로필 가져오기
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


//null일 수도 있는 3가지 칼럼들 처리

// home_background_image가 null이면 키를 제외
if (is_null($partnerProfile['home_background_image'])) {
    unset($partnerProfile['home_background_image']);
}

// profile_background_image가 null이면 키를 제외
if (is_null($partnerProfile['profile_background_image'])) {
    unset($partnerProfile['profile_background_image']);
}

// profile_message가 null이면 키를 제외
if (is_null($partnerProfile['profile_message'])) {
    unset($partnerProfile['profile_message']);
}
//optString을 호출할 때 제공된 기본값이 반환



// 클라이언트에게 JSON 형태로 응답
header('Content-Type: application/json');

if ($partnerProfile) {
    echo json_encode([
        'success' => true,
        'data' => $partnerProfile
    ]);


} else {
    // 프로필이 존재하지 않는 경우
    echo json_encode([
        'success' => false,
        'message' => 'User profile not found.'
    ]);
}



?>