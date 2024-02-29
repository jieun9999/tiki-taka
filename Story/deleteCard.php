<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

// JSON 형식의 요청 본문 데이터 읽기
$json = file_get_contents('php://input');
// JSON 데이터를 PHP 배열로 변환
$data = json_decode($json, true);

$cardId = isset($data['cardId']) ? $data['cardId'] : null;

if($cardId !== null){
    try{
        //해당 cardId로 부터 folderId 가져오기
        $sql = "SELECT folder_id FROM storyCard WHERE card_id = :card_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':card_id', $cardId, PDO::PARAM_INT);
        $stmt->execute();
        $folderId = $stmt->fetchColumn();

        if($folderId){
            //해당 folderId를 참조하는 다른 스토리 카드의 수 계산
            $sql = "SELECT COUNT(*) FROM storyCard WHERE folder_id = :folder_id
                    AND card_id != :card_id";
            $stmt = $conn->prepare($sql);
             $stmt->bindParam(':folder_id', $folderId, PDO::PARAM_INT);
             $stmt->bindParam(':card_id', $cardId, PDO::PARAM_INT);
             $stmt->execute();
             $count = $stmt->fetchColumn();

             // 폴더 삭제 여부를 추척하는 변수 초기화
             $folderDeleted = false;

             if($count == 0){
                // 폴더를 참조하는 다른 스토리 카드가 없으므로 폴더 삭제
                $sql = "DELETE FROM storyFolder WHERE folder_id = :folder_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':folder_id', $folderId, PDO::PARAM_INT);
                $stmt->execute();
                $folderDeleted = true; 
             }

             $sql = "DELETE FROM storyCard WHERE card_id = :card_id";
             $stmt = $conn->prepare($sql);
             $stmt->bindParam(':card_id', $cardId, PDO::PARAM_INT);
             $success = $stmt->execute();

             if($success){
               // 삭제 성공 응답, 폴더 삭제 여부를 함께 전달
                echo json_encode(['success' => true, 'folderDeleted' => $folderDeleted]);
             
             }else{
                echo json_encode(['success' => false, 'folderDeleted' => $folderDeleted]);
                 
            }
    
        }
       
    }catch (PDOException $e) {
        // 오류 처리
        error_log("Database error: " . $e->getMessage());
    
    }

}else{
    error_log("No commentId provided in request");
}
?>