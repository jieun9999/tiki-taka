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
    error_log("JSON empty");
}
$userProfile = json_decode($json);

// 디코드 후, 객체 접근할때 클라이언트에서 정의된 @SerializedName 어노테이션의 값에 해당하는 속성으로 접근
// 그렇지 않으면 에러가 발생함
$userId = $userProfile->user_id;
$profileImage = $userProfile->profile_image; // Base64 인코딩된 문자열
$gender = $userProfile->gender;
$name = $userProfile->name;
$birthday = $userProfile->birthday;
$meetingDay = $userProfile->first_date;
$agreeTerms = $userProfile->agree_app_terms;
$agreePrivacy = $userProfile->agree_privacy_policy;

// INSERT 쿼리 작성
$sql = "INSERT INTO userProfile (user_id, profile_image, gender, name, birthday, first_date, agree_app_terms, agree_privacy_policy) 
        VALUES (:userId, :profileImage, :gender, :name, :birthday, :meetingDay, :agreeTerms, :agreePrivacy)";

// PreparedStatement 생성
$stmt = $conn->prepare($sql);

// 데이터 타입을 지정하지 않으면, PHP는 기본적으로 모든 값들을 문자열(PDO::PARAM_STR)로 처리합니다.
// 특히, 정수값이나 불린값이 그러함
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->bindParam(':profileImage', $profileImage, PDO::PARAM_STR);
$stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
$stmt->bindParam(':name', $name, PDO::PARAM_STR);
$stmt->bindParam(':birthday', $birthday, PDO::PARAM_STR);
$stmt->bindParam(':meetingDay', $meetingDay, PDO::PARAM_STR);
$stmt->bindParam(':agreeTerms', $agreeTerms, PDO::PARAM_BOOL);
$stmt->bindParam(':agreePrivacy', $agreePrivacy, PDO::PARAM_BOOL);


// 쿼리 실행
$result = $stmt->execute();

// 결과 확인 및 출력
if ($result) {
    echo json_encode(["success" => true, "message" => "저장 성공!"]);
} else {
    echo json_encode(["success" => false, "message" => "저장 실패"]);
}

?>