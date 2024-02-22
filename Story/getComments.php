<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결
$cardId = isset($_GET['cardId']) ? $_GET['cardId'] : null;

//1. cardId가 존재하는지 확인
if($cardId !== null){
    // comment 테이블과 userProfile 테이블을 조인하여, 댓글id, 카드id, 유저id, 댓글내용, 댓글생성날짜, 프로필 이미지를 가져온다.
    $sql = "SELECT c.comment_id, c.card_id, c.user_id, c.comment_text, c.created_at, u.profile_image
            FROM comment AS c
            JOIN userProfile AS u ON c.user_id = u.user_id
            WHERE card_id = :cardId
            ORDER BY c.created_at ASC";
            //(오름차순)가장 이른 날짜부터 가장 늦은 날짜 순으로 정렬

    $stmt = $conn->prepare($sql);
    $stmt ->execute([':cardId' => $cardId]);
    $commentsResult = [];
    //조회된 데이터들을 배열에 추가함
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