<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

// JSON 형식의 요청 본문 데이터 읽기
$json = file_get_contents('php://input');
// JSON 데이터를 PHP 배열로 변환
$data = json_decode($json, true);

$cardId = isset($data['cardId']) ? $data['cardId'] : null;
$text = isset($data['text']) ? $data['text'] : null;
$partnerId = isset($data['partnerId']) ? $data['partnerId'] : null;
$userId = isset($data['userId']) ? $data['userId'] : null;

if($cardId !== null && $text !== null && $partnerId != null && $userId != null){

    try{
        $sql = "UPDATE storyCard SET memo = :memo
                WHERE card_id = :cardId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':memo', $text, PDO::PARAM_STR);
        $stmt->bindParam(':cardId', $cardId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if($success){
            if($stmt -> rowCount() > 0){
            // 실제로 데이터가 업데이트 되었음
            // 알림 데이터 구성
            require_once '../FCM/selectFcmTokenAndProfileImg.php';
            $result = selectFcmTokenAndProfileImg($conn, $partnerId, $userId);
            $tokenRow = $result['fcmToken'];
            $token = $tokenRow['token'];
            $profileInfo = $result['profileInfo'];
            $userImg = $profileInfo['profile_image'];
            $name = $profileInfo['name'];
            $messageData = [
                'flag' => 'story_memo_update_notification',
                'title' => 'tiki taka',
                'body' => $name.'님이 메모를 수정했습니다. 확인해보세요!',
                'userProfile' => $userImg,
                'cardId' => $cardId
            ];

            // FCM 서버에 알림 데이터를 보내기
            // require_once : 다른 파일을 현재 스크립트에 포함시킬 때 사용
            require_once '../FCM/sendFcmNotification.php';
            $resultFCM = sendFcmNotification($token, $messageData);

                if($resultFCM){
                    echo json_encode(["success" => true]);
                }else{
                    error_log("게시 실패 ㅠ: sendFcmNotification() 실행시 문제가 생김");
                }
            }else{
                echo json_encode(['success' => false]);
                error_log("No data updated");
            }
         }else{
             echo json_encode(['success' => false]);
             error_log("Query execution failed.");
         }

    }catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    
    }

}else{
    error_log("text, cardId, PartnerId, userId is required.");
}

?>