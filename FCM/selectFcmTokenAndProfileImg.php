<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 데이터베이스 연결 객체를 인자로 받음
function selectFcmTokenAndProfileImg($conn, $partnerId, $userId){

    try{
    $sql = "SELECT token
            FROM fcmToken 
            WHERE user_id = :partnerId";
    $stmt = $conn->prepare($sql);
    $stmt ->execute([':partnerId' => $partnerId]);
    $fcmToken = $stmt -> fetch(PDO::FETCH_ASSOC);

    $sql2 = "SELECT profile_image, name 
            FROM userProfile
            WHERE user_id = :userId";
    $stmt2 = $conn->prepare($sql2);
    $stmt2 ->execute([':userId' => $userId]);
    $profileInfo = $stmt2 -> fetch(PDO::FETCH_ASSOC);

        // $profileInfo와 $fcmToken이 모두 존재할 때만 값을 반환
        if ($profileInfo !== null && $fcmToken !== null) {
            return [
                'fcmToken' => $fcmToken,
                'profileInfo' => $profileInfo
            ];
        } else {
            return null; // $profileInfo나 $fcmToken 중 하나라도 null이면 null 반환
        }

    }catch(PDOException $e){
        // 오류 처리
        error_log("Database error: " . $e->getMessage());
        return null;
    }

}



?>