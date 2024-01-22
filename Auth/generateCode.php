<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결

// 인증번호 생성
$userId = $_GET['userId'];
$invitationCode = substr(str_shuffle("0123456789"),0, 8);
$codeDate = date("Y-m-d H:i:s");

//데이터 베이스에 저장
$sql = "UPDATE userAuth SET invite_code = :invitationCode,
invite_code_date = :codeDate
                        WHERE user_id = :userId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':invitationCode', $invitationCode);
$stmt->bindParam(':codeDate', $codeDate);
$stmt ->bindParam(':userId', $userId);
$stmt->execute();

// 클라이언트에 응답 전송
echo json_encode(array("invitationCode" => $invitationCode, "codeDate" => $codeDate));

?>