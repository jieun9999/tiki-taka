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

function updateStoryCardLikes($conn, $cardId, $isLiked, $column){
    try{
        $sql = "UPDATE storyCard SET $column = :likes
                WHERE card_id = :cardId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':likes', $isLiked, PDO::PARAM_BOOL);
        $stmt->bindParam(':cardId', $cardId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if($success){
            if($stmt ->rowCount() > 0){
                // 실제로 데이터가 업데이트 되었음
                echo json_encode(['success' => true]);
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
        updateStoryCardLikes($conn, $cardId, $isLiked, 'user_a_likes');
        
    }else{
    
        updateStoryCardLikes($conn, $cardId, $isLiked, 'user_b_likes');
    }
    

}else{
    error_log("No cardId or userId or isLiked provided in request");
}

?>