<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

try{

// JSON 데이터를 PHP에서 받기
$json = file_get_contents('php://input');
// 받은 JSON 데이터를 로그에 기록
error_log("Received JSON: " . $json);
$data = json_decode($json);

//PHP 코드에서 이 객체의 속성에 접근할 때는 JSON 데이터의 키 이름을 그대로 사용
$cardId = $data -> id;
$userId = $data -> user_id;
$commentText = $data -> comment_text;
$partnerId = $data -> partnerId;

// 댓글 INSERT 쿼리 준비 및 실행
$sql = "INSERT INTO comment (card_id, user_id, comment_text) VALUES (:cardId, :userId, :commentText)";
$stmt = $conn -> prepare($sql);
$success = $stmt -> execute([':cardId' => $cardId, ':userId' => $userId, ':commentText' => $commentText]);

// 선택된 카드조회
$sql = "SELECT * FROM storyCard WHERE card_id = :cardId";
$stmt = $conn->prepare($sql);
$success2 = $stmt->execute([':cardId' => $cardId]);
$selectedCard = $stmt->fetch(PDO::FETCH_ASSOC);
$dataType = $selectedCard['data_type'];

if($success && $success2){

    // 알림 데이터 구성
    require_once '../FCM/selectFcmTokenAndProfileImg.php';
    $result = selectFcmTokenAndProfileImg($conn, $partnerId, $userId);
    $tokenRow = $result['fcmToken'];
    $token = $tokenRow['token'];
    $profileInfo = $result['profileInfo'];
    $userImg = $profileInfo['profile_image'];
    $name = $profileInfo['name'];
    $messageData = [
        'flag' => 'story_comment_notification',
        'type' => $dataType,
        'title' => 'tiki taka',
        'body' => $name.'님이 댓글을 추가했습니다. 확인해보세요!',
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
    
}


}catch(Exception $e){
    error_log("댓글 추가 중 오류가 발생했습니다");

}


?>