<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결

// 이미지를 전송할때는 멀티파트 폼 데이터 전송형식을 사용하는 것이 훨씬 더 효율적이고, 안정적이다.
// base64 인코딩으로는 오류 발생함. s3상에서 계속 깨져서 나옴

// 1.S3Uploader 클래스 불러오기
require '/var/www/html/Story/S3Uploader.php';
// AWS S3 접속 정보
$s3AccessKeyFile = '/var/www/html/Story/s3_access_key.txt';
$s3SecretKeyFile = '/var/www/html/Story/s3_secret_key.txt';
$s3Region = 'ap-northeast-2';
$s3Bucket = 'jieun-s3-bucket';

// 접속 정보 읽기
$s3AccessKey = file_get_contents($s3AccessKeyFile); 
$s3SecretKey = file_get_contents($s3SecretKeyFile); 

// S3 업로더 인스턴스 생성
$s3Uploader = new S3Uploader($s3AccessKey, $s3SecretKey, $s3Region, $s3Bucket, $conn);

// 2.멀티파트 폼 데이터로 전송된 데이터를 받습니다
$userId = $_POST['userId'];
$image = $_FILES['image']; 
$contentType = $image['type']; // 파일 타입

// 3. s3Uploader 로 이미지 업로드
if (strpos($contentType, "image/") === 0) {
    $key = 'profile/' . date('Y/m/d/') . $image['name']; // S3에 저장될 객체의 키 (파일 이름)
    $filePath = $image['tmp_name']; // 임시 파일 경로

    $result = $s3Uploader -> uploadSingle($key, $filePath, $contentType);
    if ($result['success']) {
        $image = $result['url']; // s3에서 받은 url 할당
    } else {
        // 업로드 실패한 경우
    error_log("Upload failed: image" . $result['message'] . "\n");
    }
}else{
    error_log("image type이 아닙니다");
}

// 쿼리 작성
    // 1. userAuth 테이블에서 partner_id 조회
    $sql = "SELECT partner_id FROM userAuth WHERE user_id = :userId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $partnerId = $stmt->fetchColumn();

    if ($partnerId) {
    // 2. user_id의 home_background_image 업데이트
    $sql = "UPDATE userProfile SET home_background_image = :image WHERE user_id = :userId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':image', $image);
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();

    // 3. partner_id의 home_background_image 업데이트
    $sql = "UPDATE userProfile SET home_background_image = :image WHERE user_id = :partnerId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':image', $image);
    $stmt->bindParam(':partnerId', $partnerId);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "저장 성공!"]);
    } else {
    // partner_id가 없는 경우
    echo json_encode(["success" => false, "message" => "파트너를 찾을 수 없습니다"]);
    }

?>