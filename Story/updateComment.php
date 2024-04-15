<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

try{

$json = file_get_contents('php://input');
$data = json_decode($json);

//PHP 코드에서 이 객체의 속성에 접근할 때는 JSON 데이터의 키 이름을 그대로 사용
$commentId = $data -> id;
$userId = $data -> user_id;
$commentText = $data -> comment_text;
$partnerId = $data -> partnerId;

$sql = "SELECT card_id from comment WHERE comment_id = :commentId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':commentId', $commentId, PDO::PARAM_INT);
        $success = $stmt->execute();
        if($success){
            $result = $stmt -> fetch(PDO::FETCH_ASSOC);
            $cardId = $result['card_id'];
        }
 
// 선택된 카드조회
$sql = "SELECT * FROM storyCard WHERE card_id = :cardId";
$stmt = $conn->prepare($sql);
$success = $stmt->execute([':cardId' => $cardId]);
if($success){
    $selectedCard = $stmt->fetch(PDO::FETCH_ASSOC);
    $dataType = $selectedCard['data_type'];
}

$sql2 = "UPDATE comment SET comment_text = :commentText
        WHERE comment_id = :commentId";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bindParam(':commentText', $commentText, PDO::PARAM_STR);
        $stmt2->bindParam(':commentId', $commentId, PDO::PARAM_INT);
        $success2 = $stmt2->execute();

        if($success2){
            if($stmt2 -> rowCount() > 0){
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
                'flag' => 'story_comment_update_notification',
                'type' => $dataType,
                'title' => 'tiki taka',
                'body' => $name.'님이 댓글을 수정했습니다. 확인해보세요!',
                'userProfile' => $userImg,
                'cardId' => $cardId // 여기 추가하기
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

}catch(Exception $e){
    error_log("댓글 수정 중 오류가 발생했습니다");

}

?>