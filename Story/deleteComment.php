<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';
$commentId = isset($_GET['commentId']) ? $_GET['commentId'] : null;
 error_log("story commentId: " . $commentId);


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