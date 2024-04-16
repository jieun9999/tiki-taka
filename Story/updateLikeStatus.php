<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

// JSON 형식의 요청 본문 데이터 읽기
$json = file_get_contents('php://input');
// JSON 데이터를 PHP 배열로 변환
$data = json_decode($json, true);

$cardId = isset($data['cardId']) ? $data['cardId'] : null;
$userId = isset($data['userId']) ? $data['userId'] : null;
$isLiked = isset($data['isLiked']) ? $data['isLiked'] : null;
$partnerId = isset($data['partnerId']) ? $data['partnerId'] : null;
error_log("userId".$userId);
error_log("partnerId".$partnerId);

function updateStoryCardLikes($conn, $cardId, $isLiked, $column, $partnerId, $userId){
    try{
        $sql = "UPDATE storyCard SET $column = :likes
                WHERE card_id = :cardId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':likes', $isLiked, PDO::PARAM_BOOL);
        $stmt->bindParam(':cardId', $cardId, PDO::PARAM_INT);
        $success = $stmt->execute();

        // 선택된 카드조회
        $sql = "SELECT * FROM storyCard WHERE card_id = :cardId";
        $stmt = $conn->prepare($sql);
        $success2 = $stmt->execute([':cardId' => $cardId]);
        $selectedCard = $stmt->fetch(PDO::FETCH_ASSOC);
        $dataType = $selectedCard['data_type'];

        if($success && $success2){
            if($stmt ->rowCount() > 0){
                // 실제로 데이터가 업데이트 되었음
                if($isLiked){ // 좋아요 추가한 경우 알림 보냄

                    // 알림 데이터 구성
                    require_once '../FCM/selectFcmTokenAndProfileImg.php';
                    $result = selectFcmTokenAndProfileImg($conn, $partnerId, $userId);
                    $tokenRow = $result['fcmToken'];
                    $token = $tokenRow['token'];
                    $profileInfo = $result['profileInfo'];
                    $userImg = $profileInfo['profile_image'];
                    $name = $profileInfo['name'];
                    $messageData = [
                        'flag' => 'story_like_notification',
                        'type' => $dataType,
                        'title' => 'tiki taka',
                        'body' => $name.'님이 좋아요를 눌렀습니다. 확인해보세요!',
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

                }else{ // 좋아요 해체한 경우, 알림 안 보냄
                  echo json_encode(['success' => true]);
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
}

if($cardId !== null && $userId !== null && $isLiked !== null){
    if($partnerId > $userId){
        updateStoryCardLikes($conn, $cardId, $isLiked, 'user_a_likes', $partnerId, $userId);
        
    }else{
        updateStoryCardLikes($conn, $cardId, $isLiked, 'user_b_likes', $partnerId, $userId);
    }
    

}else{
    error_log("No cardId or userId or isLiked provided in request");
}

?>