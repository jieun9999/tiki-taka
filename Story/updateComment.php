<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

try{

$json = file_get_contents('php://input');
$data = json_decode($json);

//PHP 코드에서 이 객체의 속성에 접근할 때는 JSON 데이터의 키 이름을 그대로 사용
$commentId = $data -> comment_id;
$commentText = $data -> comment_text;

$sql = "UPDATE comment SET comment_text = :commentText
            WHERE comment_id = :commentId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':commentText', $commentText, PDO::PARAM_STR);
        $stmt->bindParam(':commentId', $commentId, PDO::PARAM_INT);
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

}catch(Exception $e){
    error_log("댓글 수정 중 오류가 발생했습니다");

}

?>