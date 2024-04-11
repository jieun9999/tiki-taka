<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

// 1. s3에 동영상 파일 업로드 하기
require 'S3Uploader.php';

// AWS S3 접속 정보
$s3AccessKeyFile = '/var/www/html/Story/s3_access_key.txt';
$s3SecretKeyFile = '/var/www/html/Story/s3_secret_key.txt';
$s3Region = 'ap-northeast-2';
$s3Bucket = 'jieun-s3-bucket';

// 접속 정보 읽기
$s3AccessKey = file_get_contents($s3AccessKeyFile); 
$s3SecretKey = file_get_contents($s3SecretKeyFile); 

// S3 업로더 인스턴스 생성
$s3Uploader = new S3Uploader($s3AccessKey, $s3SecretKey, $s3Region, $s3Bucket);

// 2.멀티파트 폼 데이터로 전송된 데이터를 받습니다
//텍스트 데이터
$userId = $_POST['userId'];
$title = $_POST['title'];
$location = $_POST['location'];
$comments = isset($_POST['comments']) ? $_POST['comments']: null;
$partnerId = $_POST['partnerId'];
$folderId = isset($_POST['folderId']) ? $_POST['folderId']: null;

//이미지 파일 데이터
// (1)displayImage 처리
$displayImage = $_FILES['displayImage']; // 단일 파일 처리
$contentType = $displayImage['type']; // 파일 타입
// $displayImageContent = print_r($_FILES['displayImage'], true);
// error_log("Received ['displayImage']: " . $displayImageContent);

// 파일 타입이 이미지인 경우에만 파일 업로드를 시도
// 문자열인 경우에는 이미 서버에 업로드 된, 웹 경로이기 때문에 다시 서버에 올릴 필요가 없음
if (strpos($contentType, "image/") === 0) {
    $key = 'uploads/' . date('Y/m/d/') . $displayImage['name']; // S3에 저장될 객체의 키 (파일 이름)
    $filePath = $displayImage['tmp_name']; // 임시 파일 경로

    $result = $s3Uploader -> upload($key, $filePath, $contentType);
    if ($result['success']) {
        $displayImage = $result['url']; // s3에서 받은 url 할당
    } else {
        // 업로드 실패한 경우
    error_log("Upload failed: displayImage" . $result['message'] . "\n");
    }
}
else{
    // 클라이언트에서 웹경로 데이터를 보내준 경우
    // full_path 정보는 이미지 파일의 웹 경로를 나타내고 있습니다.
    // 이는 클라이언트가 이미지를 업로드하는 대신, 이미 인터넷 상에 호스팅되어 있는 이미지의 URL을 직접 전송하여 사용하고자 할 때 발생하는 경우입니다.
    $displayImage = $displayImage['full_path'];
    // error_log("displayImage: " . print_r($displayImage, true));

}

// (2)uris처리
// 파일이 업로드된 각각의 파일에 대해 반복하여 처리
if (isset($_FILES['uris'])) {
    // $urisContent = print_r($_FILES['uris'], true);
    // error_log("Received ['uris']: " . $urisContent);
    $fileCount = count($_FILES['uris']['name']); // 여러 파일 처리

    for ($i = 0; $i < $fileCount; $i++) {
        $tmpFilePath = $_FILES['uris']['tmp_name'][$i];
        $originalFileName = $_FILES['uris']['name'][$i];
        $contentType = $_FILES['uris']['type'][$i];
        // S3에 업로드할 객체 키 생성
        $key = 'uploads/' . date('Y/m/d/') . $originalFileName;

        // 파일 업로드 시도
        $result = $s3Uploader->upload($key, $tmpFilePath, $contentType);

        // 업로드 결과에 따라 처리
        if ($result['success']) {
            // 성공적으로 업로드된 경우 처리
            $uris[] = $result['url'];
        } else {
            // 업로드 실패 시 처리
            error_log("Upload failed: uri" . $result['message'] . "\n");
        }

    }
}

// 3. DB에 url 저장하기
try{
    //트랜잭션 시작
    $conn -> beginTransaction();

    //기존 폴더에 추가하는 경우, 카드 수 검사 로직
    if(!empty($folderId)){

        // 현재 folderId에 저장된 사진의 수 확인
        $sqlCount = "SELECT COUNT(*) FROM storyCard WHERE folder_id = :folderId";
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->execute([':folderId' => $folderId]);
        $currentCardsCount = $stmtCount->fetchColumn();

        // 새로운 카드와 기존 카드의 총합 검사
        $newCardsCount = count($uris);
        $totalCardsCount = $currentCardsCount + $newCardsCount;

        if ($totalCardsCount > 10) {
            echo json_encode(["success" => false, "message" => "한 폴더에 스토리 카드는 최대 10장까지 추가할 수 있습니다."]);
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
    
    // video_thumbnail 칼럼을 아예 삭제하고, video 칼럼만 존재함
    // 스토리 카드 INSERT 쿼리 준비 및 실행
    $sqlCard = "INSERT INTO storyCard (folder_id, user_id, video, data_type) VALUES (:folderId, :userId, :video, :dataType)";
    $stmtCard = $conn->prepare($sqlCard);
    $cardIds = []; 
    // 삽입된 각 스토리 카드의 ID를 저장할 배열
    foreach($uris as $uri){
    $stmtCard->execute([':folderId' => $folderId, ':userId' => $userId, ':video' => $uri, ':dataType' => "video"]);
    $cardIds[] = $conn -> lastInsertId();
    }
     
    // storyFolder 테이블의 display_image를 업데이트
    if(!empty($displayImage)){
    $sqlUpdateFolder = "UPDATE storyFolder SET display_image = :displayImage, title = :title, location = :location
                                     WHERE folder_id = :folderId";
    $stmtUpdateFolder = $conn->prepare($sqlUpdateFolder);
    $stmtUpdateFolder->execute([':displayImage' => $displayImage, ':folderId' => $folderId, ':title' => $title,':location' => $location]);
    }

    // 댓글 INSERT 쿼리 준비 및 실행
    //쿼리 준비는 반복문 바깥에서 한 번만 수행
    $sqlComment = "INSERT INTO comment (card_id, user_id, comment_text) VALUES (:cardId, :userId, :commentText)";
    $stmtComment = $conn->prepare($sqlComment);

        if (!empty($comments) && is_array($data->comments)){
        foreach($data -> comments as $index => $commentText){
            // $commentItem 대신 $commentText를 사용합니다. $commentText는 직접 문자열입니다.
            $cardId = $cardIds[$index];
            //댓글과 스토리 카드의 순서 일치: $data->comments 배열에 있는 댓글의 순서와 $cardIds 배열에 저장된 스토리 카드 ID의 순서가 일치
            $stmtComment ->execute([':cardId' => $cardId, ':userId' => $userId, ':commentText' => $commentText]);
        }
    }
    
    // 모든 쿼리가 성공적으로 실행되면, 트랜잭션 커밋
    $conn->commit();

    // 4. 알림 데이터 구성
    require_once '../FCM/selectFcmTokenAndProfileImg.php';
    $result = selectFcmTokenAndProfileImg($conn, $partnerId);
    $token = $result['token'];
    $userProfile = $result['profile_image'];
    $name = $result['name'];
    $messageData = [
        'flag' => 'story_video_notification',
        'title' => 'tiki taka',
        'body' => $name.'님이 동영상을 추가했습니다. 확인해보세요!',
        'userProfile' => $userProfile,
        'folderId' => $folderId
    ];

    // FCM 서버에 알림 데이터를 보내기
    // require_once : 다른 파일을 현재 스크립트에 포함시킬 때 사용
    require_once '../FCM/sendFcmNotification.php';
    $resultFCM = sendFcmNotification($token, $messageData);

    if($resultFCM){
        echo json_encode(["success" => true, "message" => "게시 성공!"]);
    }else{
        echo json_encode(["success" => false, "message" => "게시 실패 ㅠ: sendFcmNotification() 실행시 문제가 생김"]);
    }

}catch(PDOException $e) {
    // 오류 발생 시 트랜잭션 롤백
    $conn->rollback();
    // 실패 응답
    echo json_encode(["success" => false, "message" => "게시 실패 ㅠ: " . $e->getMessage()]);
}

?>