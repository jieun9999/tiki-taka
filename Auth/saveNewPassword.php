<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require '../db_connect.php'; // 데이터베이스 연결

$email = $_POST['email']; //유저로부터 받은 이메일
$tempPassword = $_POST['temporaryPassword'];  // 유저로부터 받은 임시 비밀번호
$newPassword = $_POST['newPassword'];   // 유저로부터 받은 새 비밀번호

// 현재 시간
$currentDatetime = date('Y-m-d H:i:s');

// 임시비번 일치 여부 및 임시비번생성 날짜와 현재시간 비교
//이메일 기준 임시비번이 일치하고, 인증번호 생성 시간이 현재 시간으로부터 10분 이내인 경우에만 인증 성공으로 간주
$sql = "SELECT * FROM userAuth WHERE email = :email";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':email', $email);
$stmt->execute();                                
$result = $stmt->fetch(PDO::FETCH_ASSOC);

//1. 임시 비밀번호 검증
if ($result) {
    //db에서 임시비번 가져오기
    $db_tempPassword = $result['temporary_password'];

    //사용자가 입력한 임시비번과 db임시비번이 일치하는 지 검사
    if($tempPassword == $db_tempPassword){
        //일치한다면, 만료되었는지 검사

        $tempPassDate = $result['temporary_password_date'];
        //생성 시간과 현재 시간을 비교하여 10분 이내에 유효한지 확인
        $diff = strtotime($currentDatetime) - strtotime($tempPassDate);
    
        if ($diff <= 600) {
            // 만료안됨. 유효한 임시 비밀번호 일 경우, db에 새 비밀번호 저장

            //2. 새 비밀번호 저장
            //비밀번호 해싱
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            //쿼리 생성 및 실행
            $updateSql = "UPDATE userAuth SET password = :password WHERE email = :email";
            $stmt = $conn-> prepare($updateSql);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $upateResult = $stmt->execute();

            if ($upateResult) {

                // rowCount()를 사용하여 업데이트된 행의 수 확인
                if ($stmt->rowCount() == 1) {
                    echo json_encode(["success" => true, "message" => "비밀번호가 변경되었습니다"]);

                } else {
                    echo json_encode(["success" => false, "message" => "비밀번호가 변경에 실패하였습니다"]);

                }
            } else {
                // 쿼리 실행 실패
                echo json_encode(["success" => false, "message" => "비밀번호 변경 쿼리문에 문제가 있습니다"]);
            }
            
        } else {
            echo json_encode(["success" => false, "message" => "임시 비밀번호가 만료되었습니다"]);
        }

    }else{
        echo json_encode(["success" => false, "message" => "임시 비밀번호가 일치하지 않습니다"]);

    }
} else {
    echo json_encode(["success" => false, "message" => "해당 이메일과 관련된 유저 정보가 없습니다"]);
}
?>