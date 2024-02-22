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
$cardId = $data -> card_id;
$userId = $data -> user_id;
$commentText = $data -> comment_text;

// 댓글 INSERT 쿼리 준비 및 실행
$sql = "INSERT INTO comment (card_id, user_id, comment_text) VALUES (:cardId, :userId, :commentText)";
$stmt = $conn -> prepare($sql);
$success = $stmt -> execute([':cardId' => $cardId, ':userId' => $userId, ':commentText' => $commentText]);

if($success){
   echo json_encode(['success' => true]);

}else{
    echo json_encode(['success' => false]);
    
}


}catch(Exception $e){
    error_log("댓글 추가 중 오류가 발생했습니다");

}


?>