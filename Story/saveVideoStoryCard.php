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

// (1)uri처리
// 파일이 업로드된 각각의 파일에 대해 반복하여 처리
if (isset($_FILES['uri'])) {

        $tmpFilePath = $_FILES['uri']['tmp_name'];
        $originalFileName = $_FILES['uri']['name'];
        $contentType = $_FILES['uri']['type'];

        // S3에 업로드할 객체 키 생성
        $key = 'uploads/' . date('Y/m/d/') . $originalFileName;

        // 파일 업로드 시도
        $result = $s3Uploader->upload($key, $tmpFilePath, $contentType);

        // 업로드 결과에 따라 처리
        if ($result['success']) {
            // 성공적으로 업로드된 경우 처리
            $videoUrl = $result['url'];
        } else {
            // 업로드 실패 시 처리
            error_log("Upload failed: uri" . $result['message'] . "\n");
             }
    }

// (2)displayImage 처리
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

//(3) 썸네일 얻기
// ffmpeg로 동영상 Url에서 썸네일 jpg를 추출한다

    $originalThumbnailName = pathinfo($videoUrl, PATHINFO_FILENAME) . "_thumbnail.jpg"; // 원본 파일명에서 확장자를 제외하고 '_thumbnail.jpg' 추가
    $thumbnailPath = '/tmp/thumbnail.jpg'; // 썸네일을 임시로 저장할 경로

    // ffmpeg를 사용하여 썸네일 생성
    $ffmpegCommand = "ffmpeg -i '{$videoUrl}' -ss 00:00:01 -vframes 1 {$thumbnailPath} 2>&1";
    exec($ffmpegCommand, $output, $returnVar);
    
    // ffmpeg 명령의 실행 결과 확인
    if ($returnVar === 0) {
        // 썸네일 생성 성공
        $thumbnailKey = 'thumbnails/' . date('Y/m/d/') . $originalThumbnailName;
        $contentType = 'image/jpeg';

        // S3에 썸네일 업로드
        $uploadResult = $s3Uploader->upload($thumbnailKey, $thumbnailPath, $contentType);
        if ($uploadResult['success']) {
            // 썸네일 업로드 성공, S3 URL 반환
            $thumbnailUrl = $uploadResult['url'];
            $displayImage = $thumbnailUrl;
        } else {
            // 썸네일 업로드 실패
            error_log("Upload failed: thumbnail " . $uploadResult['message']);
        }
    } else {
        // 썸네일 생성 실패
        error_log("Thumbnail creation failed: " . implode("\n", $output));
    }

    // 임시 파일 삭제
    unlink($thumbnailPath);


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
        $newCardsCount = 1;
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
    
    // 추출한 썸네일 url을 video_thumbnail 칼럼에 추가
    // 스토리 카드 INSERT 쿼리 준비 및 실행
    $sqlCard = "INSERT INTO storyCard (folder_id, user_id, video, data_type, video_thumbnail) VALUES (:folderId, :userId, :video, :dataType, :video_thumbnail)";
    $stmtCard = $conn->prepare($sqlCard);
    $cardIds = []; 

    // 삽입된 스토리 카드의 ID를 저장할 배열
    // 댓글 배열 고치는 것이 복잡해서, 댓글 배열은 이미지 카드와 같게 놔둠
    $stmtCard->execute([':folderId' => $folderId, ':userId' => $userId, ':video' => $videoUrl, ':dataType' => "video", ':video_thumbnail' =>$thumbnailUrl]);
    // Retrieve the last inserted story card ID
    $cardId = $conn->lastInsertId();

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
    
        if (!empty($comments) && is_array($data->comments) && isset($data->comments[0])){
            // Prepare the INSERT query for comments
            $sqlComment = "INSERT INTO comment (card_id, user_id, comment_text) VALUES (:cardId, :userId, :commentText)";
            $stmtComment = $conn->prepare($sqlComment);

            // Execute the comment INSERT query for the single comment
            $stmtComment->execute([
                ':cardId' => $cardId, 
                ':userId' => $userId, 
                ':commentText' => $data->comments[0]  // Assuming $data->comments[0] is the single comment
            ]);
    }
    // 모든 쿼리가 성공적으로 실행되면, 트랜잭션 커밋
    $conn->commit();

    // 4. 알림 데이터 구성
    require_once '../FCM/selectFcmTokenAndProfileImg.php';
    $result = selectFcmTokenAndProfileImg($conn, $partnerId, $userId);
    $tokenRow = $result['fcmToken'];
    $token = $tokenRow['token'];
    $profileInfo = $result['profileInfo'];
    $userImg = $profileInfo['profile_image'];
    $name = $profileInfo['name'];
    $messageData = [
        'flag' => 'story_video_notification',
        'title' => 'tiki taka',
        'body' => $name.'님이 동영상을 추가했습니다. 확인해보세요!',
        'userProfile' => $userImg,
        'folderId' => $folderId
    ];

    // FCM 서버에 알림 데이터를 보내기
    // require_once : 다른 파일을 현재 스크립트에 포함시킬 때 사용
    require_once '../FCM/sendFcmNotification.php';
    $resultFCM = sendFcmNotification($token, $messageData);

    if($resultFCM){
        echo json_encode(["success" => true, "message" => $cardId]);
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