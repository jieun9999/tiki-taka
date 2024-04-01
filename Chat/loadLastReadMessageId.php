<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; 

$currentUserId = isset($_GET['currentUserId']) ? $_GET['currentUserId'] : null;

if($currentUserId !== null){
    
    $sql = "SELECT message_id
            FROM message 
            WHERE sender_id != :currentUserId AND sender_id IS NOT NULL AND is_read = 1
            ORDER BY created_at DESC
            LIMIT 1";
            // 현재 사용자(currentUserId)가 보낸 메시지가 아니면서, 이미 읽은(is_read = 1) 메시지들 중 가장 최신의 1개

    $stmt = $conn->prepare($sql);
    $stmt ->execute([':currentUserId' => $currentUserId]);
    $lastReadMessage = $stmt->fetch(PDO::FETCH_ASSOC);   

    if(!empty($lastReadMessage)){

        //결과를 json 형식으로 클라이언트에 응답
        header('Content-Type: application/json');
         //클라이언트 측에서는 message_id 키를 가진 객체를 받게 되며, 이 객체의 message_id 프로퍼티는 원래의 숫자 타입으로 처리
        echo json_encode(['message_id' => $lastReadMessage['message_id']]);
       


    }else{
        error_log("No lastReadMessage");

    }

}else{
    error_log("No currentUserId provided in request");
}



?>