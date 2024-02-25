<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

// JSON 형식의 요청 본문 데이터 읽기
$json = file_get_contents('php://input');
// JSON 데이터를 PHP 배열로 변환
$data = json_decode($json, true);

// commentId 추출
$commentId = isset($data['commentId']) ? $data['commentId'] : null;

if($commentId !== null){
    try{

        $sql = "DELETE FROM comment WHERE comment_id = :commentId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':commentId', $commentId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if($success){
            echo json_encode(['success' => true]);
         
         }else{
             echo json_encode(['success' => false]);
             
         }

    }catch (PDOException $e) {
        // 오류 처리
        error_log("Database error: " . $e->getMessage());
    
    }

}else{
    error_log("No commentId provided in request");
}
?>