<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';
// 1. s3에 이미지 파일 업로드 하기
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
$folderId = isset($_POST['folderId']) ?$_POST['folderId'] : null;
$title = isset($_POST['title']) ? $_POST['title'] : null;
$location = isset($_POST['location']) ? $_POST['location'] : null;

//이미지 파일 데이터
// displayImage 처리
$displayImage = $_FILES['displayImage']; // 단일 파일 처리
$contentType = $displayImage['type']; // 파일 타입

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


if($folderId !== null){
    try{
        $sql = "UPDATE storyFolder SET display_image = :display_image, title = :title, location = :location
                WHERE folder_id = :folder_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':folder_id', $folderId, PDO::PARAM_INT);
        $stmt->bindParam(':display_image', $displayImage, PDO::PARAM_STR);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':location', $location, PDO::PARAM_STR);
        $success = $stmt->execute();

        header('Content-Type: application/json'); // JSON 응답 헤더 추가
        if($success){
            if($stmt ->rowCount() > 0){
                // 실제로 데이터가 업데이트 되었음
                echo json_encode(['success' => true]);
            }else{
                // 데이터가 업데이트되지 않았음을 의미할 수 있음 (이미 같은 값이었을 경우 등)
                echo json_encode(['success' => true]);
                error_log("No changes made. Data is already up-to-date.");
            }
         }else{
             echo json_encode(['success' => false]);
             error_log("Query execution failed.");
         }

    }catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    
    }
}else {
    error_log("Folder ID is required.");
}

?>