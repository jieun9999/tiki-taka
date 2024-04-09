<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 데이터베이스 연결 객체를 인자로 받음
function selectFcmTokenAndProfileImg($conn, $userId){

    try{
    $sql = "SELECT f.token, u.profile_image, u.name
            FROM fcmToken f
            JOIN userProfile u ON f.user_id = u.user_id
            WHERE f.user_id = :userId";
            
    $stmt = $conn->prepare($sql);
    $stmt ->execute([':userId' => $userId]);
    $result = $stmt -> fetch(PDO::FETCH_ASSOC);

    if ($result) {
        return $result;
    } else {
        return null;
    }

    }catch(PDOException $e){
        // 오류 처리
        error_log("Database error: " . $e->getMessage());
        return null;
    }

}



?>