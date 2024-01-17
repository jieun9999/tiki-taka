<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require 'db_connect.php'; // 데이터베이스 연결

$email = $_POST['email']; // 유저로부터 받은 이메일
$authCode = $_POST['authCode'];// 유저로부터 받은 인증번호

// 현재 시간
$currentDatetime = date('Y-m-d H:i:s');

// 1. 인증번호 일치 여부 및 인증코드 날짜와 현재 시간 비교
//이메일과 인증번호가 모두 일치하고, 인증번호 생성 시간이 현재 시간으로부터 10분 이내인 경우에만 인증 성공으로 간주
$sql = "SELECT * FROM userAuth WHERE email = :email
                                AND auth_code = :authCode";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':authCode', $authCode, PDO::PARAM_INT);
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    $authCodeDate = $result['auth_code_date'];
    // 인증 코드 생성 시간과 현재 시간을 비교하여 10분 이내에 유효한지 확인
    $diff = strtotime($currentDatetime) - strtotime($authCodeDate);

   if ($diff <= 600) {
        echo json_encode(["success" => true, "message" => "이메일 인증에 성공하였습니다"]);
    } else {
        echo json_encode(["success" => false, "message" => "인증번호가 만료되었습니다. 재발급 받으세요"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "이메일 인증에 실패하였습니다"]);
}

?>