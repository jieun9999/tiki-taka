<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

// JSON 형식의 요청 본문 데이터 읽기
$json = file_get_contents('php://input');
// JSON 데이터를 PHP 배열로 변환
$data = json_decode($json, true);

$folderId = isset($data['folder_id']) ? $data['folder_id'] : null;
$cardId = isset($data['card_id']) ? $data['card_id'] : null;

if($folderId !== null && $cardId !== null){
    try{
        $sql = "UPDATE storyCard SET folder_id = :folder_id
                WHERE card_id = :card_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':folder_id', $folderId, PDO::PARAM_INT);
        $stmt->bindParam(':card_id', $cardId, PDO::PARAM_INT);
        $success = $stmt->execute();

        header('Content-Type: application/json'); // JSON 응답 헤더 추가
        if($success){
            if($stmt ->rowCount() > 0){
                // 실제로 데이터가 업데이트 되었음
                echo json_encode(['success' => true]);
            }else{
                // 데이터가 업데이트되지 않았음을 의미할 수 있음 (이미 같은 값이었을 경우 등)
                echo json_encode(['success' => true]);
                error_log("No changes made. Data is already up-to-date.");
            }
         }else{
             echo json_encode(['success' => false]);
             error_log("Query execution failed.");
         }

    }catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    
    }
}else {
    error_log("Folder ID or Card ID is required.");
}