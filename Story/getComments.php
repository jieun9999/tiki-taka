<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결
$cardId = isset($_GET['cardId']) ? $_GET['cardId'] : null;

//1. cardId가 존재하는지 확인
if($cardId !== null){
    $sql = "SELECT * FROM comment WHERE card_id = :cardId";
    $stmt = $conn->prepare($sql);
    $stmt ->execute([':cardId' => $cardId]);

    $commentsResult = [];
    //조회된 댓글을 배열에 추가
    while($row = $stmt -> fetch(PDO::FETCH_ASSOC)){
        $commentsResult[] = $row;
    }     

    //2. 쿼리 결과가 존재하는 지 확인
    if(!empty($commentsResult)){
        //결과를 json 형식으로 클라이언트에 응답
        header('Content-Type: application/json');
        echo json_encode($commentsResult);
    }else{
        error_log("No comments found for cardId: " . $cardId);

    }

}else{
    error_log("No cardId provided in request");
}



?>