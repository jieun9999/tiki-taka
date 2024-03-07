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

if($cardId !== null && $text !== null){

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

?>