<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

// JSON 데이터를 PHP에서 받기
$json = file_get_contents('php://input');
$data = json_decode($json);

$userId = $data->userId;
$uris = $data->uris;
$title = $data->title;
$location = $data->location;
$displayImage = $data -> displayImage;

try{
    //트랜잭션 시작
    $conn -> beginTransaction();

    // 1.스토리 폴더 INSERT 쿼리 준비 및 실행
    $sqlFolder = "INSERT INTO storyFolder (user_id, data_type) VALUES (:userId, :dataType)";
    $stmtFolder = $conn->prepare($sqlFolder);
    $stmtFolder->execute([':userId' => $userId, ':dataType' => "image"]);
    $folderId = $conn->lastInsertId();

    // 2.스토리 카드 INSERT 쿼리 준비 및 실행
    $sqlCard = "INSERT INTO storyCard (folder_id, user_id, video, data_type, video_thumbnail) VALUES (:folderId, :userId, :video, :dataType, :video_thumbnail)";
    $stmtCard = $conn->prepare($sqlCard);
    $cardIds = []; 
    // 삽입된 각 스토리 카드의 ID를 저장할 배열
    foreach($uris as $uri){
        $stmtCard->execute([':folderId' => $folderId, ':userId' => $userId, ':video' => $uri, ':dataType' => "video", ':video_thumbnail' => $displayImage]);
        $cardIds[] = $conn -> lastInsertId();
    }

    // 3.storyFolder 테이블의 display_image를 업데이트
    if(!empty($displayImage)){
        $sqlUpdateFolder = "UPDATE storyFolder SET display_image = :displayImage, title = :title, location = :location
                             WHERE folder_id = :folderId";
        $stmtUpdateFolder = $conn->prepare($sqlUpdateFolder);
        $stmtUpdateFolder->execute([':displayImage' => $displayImage, ':folderId' => $folderId, ':title' => $title,':location' => $location]);
    }

    //4. 댓글 INSERT 쿼리 준비 및 실행
    // 쿼리 준비는 반복문 바깥에서 한 번만 수행
    $sqlComment = "INSERT INTO comment (card_id, user_id, comment_text) VALUES (:cardId, :userId, :commentText)";
    $stmtComment = $conn->prepare($sqlComment);

    foreach($data -> comments as $index => $commentText){
        // $commentItem 대신 $commentText를 사용합니다. $commentText는 직접 문자열입니다.
        $cardId = $cardIds[$index];
        //댓글과 스토리 카드의 순서 일치: $data->comments 배열에 있는 댓글의 순서와 $cardIds 배열에 저장된 스토리 카드 ID의 순서가 일치
        $stmtComment ->execute([':cardId' => $cardId, ':userId' => $userId, ':commentText' => $commentText]);
    }
    
    // 모든 쿼리가 성공적으로 실행되면, 트랜잭션 커밋
    $conn->commit();
    //성공응답
    echo json_encode(["success" => true, "message" => "게시 성공!"]);

}catch(PDOException $e) {
    // 오류 발생 시 트랜잭션 롤백
    $conn->rollback();
    // 실패 응답
    echo json_encode(["success" => false, "message" => "게시 실패 ㅠ: " . $e->getMessage()]);
}

?>