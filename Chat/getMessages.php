<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; 

$roomId = isset($_GET['roomId']) ? $_GET['roomId'] : null;

if($roomId !== null){
    
    $sql = "SELECT m.message_id, m.sender_id, m.content, m.created_at, m.date_marker, m.is_read, u.profile_image
            FROM message AS m
            LEFT JOIN userProfile AS u ON m.sender_id = u.user_id 
            WHERE room_id = :roomId
            ORDER BY m.created_at ASC";
            //(오름차순)가장 이른 날짜부터 가장 늦은 날짜 순으로 정렬
            // LEFT JOIN은 null인 행도 가져옴

    $stmt = $conn->prepare($sql);
    $stmt ->execute([':roomId' => $roomId]);
    $messagesResult = [];

    //조회된 데이터들을 배열에 추가함
    while($row = $stmt -> fetch(PDO::FETCH_ASSOC)){
        $messagesResult[] = $row;
    }     

    if(!empty($messagesResult)){

        //결과를 json 형식으로 클라이언트에 응답
        header('Content-Type: application/json');
        echo json_encode($messagesResult);

    }else{
        error_log("No comments found for roomId: " . $roomId);

    }

}else{
    error_log("No roomId provided in request");
}



?>