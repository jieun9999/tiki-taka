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

//null일 수도 있는 3가지 칼럼들 처리

// home_background_image가 null이면 키를 제외
if (is_null($userProfile['home_background_image'])) {
    unset($userProfile['home_background_image']);
}

// profile_background_image가 null이면 키를 제외
if (is_null($userProfile['profile_background_image'])) {
    unset($userProfile['profile_background_image']);
}

// profile_message가 null이면 키를 제외
if (is_null($userProfile['profile_message'])) {
    unset($userProfile['profile_message']);
}
//optString을 호출할 때 제공된 기본값이 반환



// 클라이언트에게 JSON 형태로 응답
header('Content-Type: application/json');

if ($userProfile) {
    echo json_encode([
        'success' => true,
        'data' => $userProfile
    ]);


} else {
    // 프로필이 존재하지 않는 경우
    echo json_encode([
        'success' => false,
        'message' => 'User profile not found.'
    ]);
}


?>