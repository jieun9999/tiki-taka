<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; 
$cardId = isset($_GET['cardId']) ? $_GET['cardId'] : null;
 error_log("cardId: " . $cardId);

//1. cardId가 존재하는지 확인
if($cardId !== null){
    // storyCard 테이블과 userProfile 테이블을 조인하여, 카드id, 카드이미지, 카드좋아요, 카드상대방좋아요, 카드생성날짜, 유저id, 유저 프로필, 유저이름을 가져온다.
    $sql = "SELECT s.card_id, s.user_id, s.user_good, s.partner_good, s.created_at, s.data_type, s.image
                ,u.user_id, u.name, u.profile_image
            FROM storyCard AS s
            JOIN userProfile AS u ON s.user_id = u.user_id
            WHERE s.card_id = :cardId";

    $stmt = $conn->prepare($sql);
    $stmt ->execute([':cardId' => $cardId]);
    $cardResult = null;

    //하나의 객체로 cardResult를 할당
    if($row = $stmt -> fetch(PDO::FETCH_ASSOC)){
        $cardResult = $row;
    }     

    //2. 쿼리 결과가 존재하는 지 확인
    if(!empty($cardResult)){

        //결과를 json 형식으로 클라이언트에 응답
        header('Content-Type: application/json');
        echo json_encode($cardResult);

    }else{
        error_log("No comments found for cardId: " . $cardId);

    }

}else{
    error_log("No cardId provided in request");
}

?>