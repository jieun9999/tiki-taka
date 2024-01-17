<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require 'db_connect.php'; // 데이터베이스 연결

$userId = $_POST['userId']; // 쉐어드에서 가져온 유저아이디
$inviCode = $_POST['inviCode'];// 유저로부터 받은 초대번호
$true = 1;

// 현재 시간
$currentDatetime = date('Y-m-d H:i:s');

// 1. 초대번호 일치 여부 및 초대코드 날짜와 현재 시간 비교
//유저아이디과 인증번호가 모두 일치하고, 인증번호 생성 시간이 현재 시간으로부터 24시간 이내인 경우에만 인증 성공으로 간주
$sql = "SELECT * FROM userAuth WHERE invite_code = :inviCode";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':inviCode', $inviCode, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    $inviCodeDate = $result['invite_code_date'];
    // 코드 생성 시간과 현재 시간을 비교하여 24시간 이내에 유효한지 확인
    $diff = strtotime($currentDatetime) - strtotime($inviCodeDate);

   if ($diff <= 86400 ) {

    // 계정 연결
    // 1. 내가 입력한 초대코드의 유저아이디를 파트너아이디로 가짐
    // 2. 상대방도 역시 나를 파트너 아이디로 가짐
    //상대방 id를 변수에 저장
    $user2Id = $result['user_id'];
    error_log("User2 ID: " . $user2Id);

    //1.
    $sqlUpdatePartnerId = "UPDATE userAuth SET partner_id = :partnerId, connect = :connect
                           WHERE user_id = :userId";
    $stmtUpdate = $conn->prepare($sqlUpdatePartnerId);
    $stmtUpdate->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':partnerId', $user2Id, PDO::PARAM_INT); 
    $stmtUpdate->bindParam(':connect', $true, PDO::PARAM_INT); 
    $updateSuccess1 = $stmtUpdate->execute();

    //2.
    $sqlUpdatePartnerIdForPartner = "UPDATE userAuth SET partner_id = :userId, connect = :connect
                                     WHERE user_id = :partnerId";
    $stmtUpdateForPartner = $conn->prepare($sqlUpdatePartnerIdForPartner);
    $stmtUpdateForPartner->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmtUpdateForPartner->bindParam(':partnerId', $user2Id, PDO::PARAM_INT);     
    $stmtUpdateForPartner->bindParam(':connect', $true, PDO::PARAM_INT); 
    $updateSuccess2 = $stmtUpdateForPartner->execute();
    //주의 :한 번 바인딩된 파라미터는 그 특정 SQL 문장의 실행에만 사용되며, 다른 SQL 문장에는 영향을 미치지 않습니다.
    //주의 : bindParam()은 변수를 참조로 받기 때문에, 직접적인 값을 할당 할수 없음

    if ($updateSuccess1 && $updateSuccess2) {

        echo json_encode(["success" => true, "message" => "연결에 성공하셨습니다"]);
    } else {
        echo json_encode(["success" => false, "message" => "연결에 실패하였습니다. partner_id 업데이트 오류"]);
    }
        
    } else {
        echo json_encode(["success" => false, "message" => "초대코드가 만료되었습니다. 재발급 받으세요"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "연결에 실패하였습니다. 초대코드를 바르게 입력하세요"]);
}

?>