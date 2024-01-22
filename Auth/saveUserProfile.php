<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require '../db_connect.php'; // 데이터베이스 연결

// JSON 데이터 읽기
// 클라이언트가 JSON 형태로 데이터를 전송하므로, php://input 스트림을 사용하여 이 데이터를 읽어야 합니다. 
// $_POST 대신 아래와 같이 수정
$json = file_get_contents('php://input');
if ($json) {
    error_log("JSON data: " . $json);
} else {
    error_log("JSON 데이터가 비어 있습니다.");
}
$userProfile = json_decode($json);

// 객체 접근 방식을 사용
$userId = $userProfile->userId;

// bindParam() 메소드는 문자열 또는 숫자 값을 요구하는데, 배열이 전달되면 이 경고가 발생합니다.
// 바이너리 데이터(이미지)를 데이터베이스에 저장하는 경우, 배열이 아닌 문자열 형태의 바이너리 데이터로 변환해야 합니다.
// 바이트 배열을 PHP의 문자열로 변환합니다.
$binaryData = implode(array_map("chr", $userProfile->profileImage));
// 각 바이트는 chr 함수를 통해 해당 바이트에 해당하는 ASCII 문자로 변환

// Base64 인코딩을 사용하여 문자열로 변환합니다.
$profileImage_base64String = base64_encode($binaryData);
// 이유: . Base64 인코딩은 이러한 바이너리 데이터를 오직 안전한 ASCII 문자로만 구성된 문자열로 변환

$gender = $userProfile->gender;
$name = $userProfile->name;
$birthday = $userProfile->birthday;
$meetingDay = $userProfile->meetingDay;
$agreeTerms = $userProfile->agreeTerms;
$agreePrivacy = $userProfile->agreePrivacy;

// INSERT 쿼리 작성
$sql = "INSERT INTO userProfile (user_id, profile_image, gender, name, birthday, first_date, agree_app_terms, agree_privacy_policy) 
        VALUES (:userId, :profileImage, :gender, :name, :birthday, :meetingDay, :agreeTerms, :agreePrivacy)";

// PreparedStatement 생성
$stmt = $conn->prepare($sql);

$stmt->bindParam(':userId', $userId, PDO::PARAM_INT); // 정수형
$stmt->bindParam(':profileImage', $profileImage_base64String, PDO::PARAM_LOB); // 대용량 바이너리 데이터 (BLOB)
$stmt->bindParam(':gender', $gender, PDO::PARAM_STR); // 문자열
$stmt->bindParam(':name', $name, PDO::PARAM_STR); // 문자열
$stmt->bindParam(':birthday', $birthday, PDO::PARAM_STR); // 문자열, 일자 형식이지만 문자열로 처리
$stmt->bindParam(':meetingDay', $meetingDay, PDO::PARAM_STR); // 문자열, 일자 형식이지만 문자열로 처리
$stmt->bindParam(':agreeTerms', $agreeTerms, PDO::PARAM_BOOL); // 불리언
$stmt->bindParam(':agreePrivacy', $agreePrivacy, PDO::PARAM_BOOL); // 불리언

// 쿼리 실행
$result = $stmt->execute();

// 결과 확인 및 출력
if ($result) {
    echo json_encode(["success" => true, "message" => "저장 성공!"]);
} else {
    echo json_encode(["success" => false, "message" => "저장 실패"]);
}

?>