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
    $user1Id = $data -> user1_id;
    $user2Id = $data -> user2_id;

    // 댓글 INSERT 쿼리 준비 및 실행
    $sql = "INSERT INTO chatRoom (user1_id, user2_id) VALUES (:user1_id, :user2_id)";
    $stmt = $conn -> prepare($sql);
    $success = $stmt -> execute([':user1_id' => $user1Id, ':user2_id' => $user2Id]);

    if($success){
        $lastInsertedId = $conn->lastInsertId();
        echo json_encode(['roomId' => $lastInsertedId]);

    }else{
        echo json_encode(['roomId' => -1]);

    }


}catch(Exception $e){
    error_log("채팅방 생성 중 오류가 발생했습니다");

}


?>