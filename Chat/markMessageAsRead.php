<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

try{
    // JSON 데이터를 PHP에서 받기
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    //PHP 코드에서 이 객체의 속성에 접근할 때는 JSON 데이터의 키 이름을 그대로 사용
    $userId = $data -> user_id;
    $messageId = $data -> message_id;
    $isRead = 1; // 값을 저장할 변수 선언
    error_log("Binding Params: userId = $userId, messageId = $messageId, isRead = $isRead");

    $sql = "UPDATE message SET is_read = :is_read
            WHERE message_id <= :message_id AND sender_id != :user_id AND sender_id IS NOT NULL ";
    $stmt = $conn -> prepare($sql);
    $stmt->bindParam(':is_read', $isRead);
    $stmt->bindParam(':message_id', $messageId);
    $stmt->bindParam(':user_id', $userId);
    $result = $stmt->execute();

    if($result){
        // rowCount()를 사용하여 업데이트된 행의 수 확인
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true]);
        }
        else{
        echo json_encode(["success" => false]);
        error_log("no update row");
        }
    }else{
        // 실패 응답
        echo json_encode(['success' => false]);
        error_log("error in sql");
    }

    }catch(PDOException $e) {

    // 오류 처리
    error_log("Database error: " . $e->getMessage());
    }

?>