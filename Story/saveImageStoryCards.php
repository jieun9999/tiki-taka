<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

// JSON 데이터를 PHP에서 받기
$json = file_get_contents('php://input');
$data = json_decode($json);

$userId = $data->userId;
$folderId = isset($data->folderId) ? $data->folderId : null; 
$uris = $data->uris;
$title = isset($data->title) ? $data->title : null;
$location = isset($data->location) ? $data->location : null;
$displayImage = $data -> displayImage;

try{
    //트랜잭션 시작
    $conn -> beginTransaction();

       // 기존 폴더에 추가하는 경우, 사진 수 검사 로직
    if(!empty($folderId)){

        // 현재 folderId에 저장된 사진의 수 확인
        $sqlCount = "SELECT COUNT(*) FROM storyCard WHERE folder_id = :folderId";
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->execute([':folderId' => $folderId]);
        $currentPhotoCount = $stmtCount->fetchColumn();

        // 새로운 사진과 기존 사진의 총합 검사
        $newPhotosCount = count($uris);
        $totalPhotosCount = $currentPhotoCount + $newPhotosCount;

        if ($totalPhotosCount > 10) {
            echo json_encode(["success" => false, "message" => "한 폴더에 사진은 최대 10장까지 추가할 수 있습니다."]);
            exit; 
        }

        // 여기서 기존 폴더의 data_type을 업데이트
        $sqlUpdateFolderDataType = "UPDATE storyFolder SET data_type = 'image' WHERE folder_id = :folderId";
        $stmtUpdateFolderDataType = $conn->prepare($sqlUpdateFolderDataType);
        $stmtUpdateFolderDataType->execute([':folderId' => $folderId]);

    }else{
        // 새 스토리 폴더 생성 로직
        $sqlFolder = "INSERT INTO storyFolder (user_id, data_type) VALUES (:userId, :dataType)";
        $stmtFolder = $conn->prepare($sqlFolder);
        $stmtFolder->execute([':userId' => $userId, ':dataType' => "image"]);
        $folderId = $conn->lastInsertId();

    }

    // 스토리 카드 INSERT 쿼리 준비 및 실행
    $sqlCard = "INSERT INTO storyCard (folder_id, user_id, image, data_type) VALUES (:folderId, :userId, :image, :dataType)";
    $stmtCard = $conn->prepare($sqlCard);
    $cardIds = []; 
    // 삽입된 각 스토리 카드의 ID를 저장할 배열
    foreach($uris as $uri){
        $stmtCard->execute([':folderId' => $folderId, ':userId' => $userId, ':image' => $uri, ':dataType' => "image"]);
        $cardIds[] = $conn -> lastInsertId();
    }

    // storyFolder 테이블의 display_image를 업데이트
    if(!empty($displayImage)){
        $sqlUpdateFolder = "UPDATE storyFolder SET display_image = :displayImage, title = :title, location = :location
                             WHERE folder_id = :folderId";
        $stmtUpdateFolder = $conn->prepare($sqlUpdateFolder);
        $stmtUpdateFolder->execute([':displayImage' => $displayImage, ':folderId' => $folderId, ':title' => $title, ':location' => $location]);
    }

    // 댓글 INSERT 쿼리 준비 및 실행
    // 쿼리 준비는 반복문 바깥에서 한 번만 수행
    $sqlComment = "INSERT INTO comment (card_id, user_id, comment_text) VALUES (:cardId, :userId, :commentText)";
    $stmtComment = $conn->prepare($sqlComment);

    // $data->comments가 설정되어 있고, 배열인지 확인
    if (isset($data->comments) && is_array($data->comments)) {
        foreach($data -> comments as $index => $commentText){
            // $commentItem 대신 $commentText를 사용합니다. $commentText는 직접 문자열입니다.
            $cardId = $cardIds[$index];
            //댓글과 스토리 카드의 순서 일치: $data->comments 배열에 있는 댓글의 순서와 $cardIds 배열에 저장된 스토리 카드 ID의 순서가 일치
            $stmtComment ->execute([':cardId' => $cardId, ':userId' => $userId, ':commentText' => $commentText]);
        }
    }
    
    // 모든 쿼리가 성공적으로 실행되면, 트랜잭션 커밋
    $conn->commit();
    echo json_encode(["success" => true, "message" => "게시 성공!"]);

}catch(PDOException $e) {

    // 오류 발생 시 트랜잭션 롤백
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "게시 실패 ㅠ: " . $e->getMessage()]);
}

?>