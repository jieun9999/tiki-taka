<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require '../db_connect.php'; // 데이터베이스 연결

$email = $_POST['email']; // 클라이언트로부터 받은 이메일
$password = $_POST['password']; // 클라이언트로부터 받은 비밀번호

//이메일로 회원정보 가져옴
$sql = "SELECT * FROM userAuth WHERE email = :email";
$stmt = $conn->prepare($sql);
$stmt-> bindParam(':email', $email, PDO::PARAM_STR);
$result = $stmt->execute(); //부울값(true, false) 을 반환


//회원정보 중 비번이 일치하는 지 검사
if($result){

    // 데이터를 실제로 가져오려면, 쿼리 실행 후 PDOStatement 객체인 $stmt를 사용해야 합니다. 
    // 여기서는 fetch() 메서드를 사용하여 결과 집합에서 다음 행을 가져옴
    $user = $stmt->fetch(PDO::FETCH_ASSOC); //결과 집합에서 한 행을 연관 배열 형태로 가져옴

    if($user){

        $db_password = $user['password']; // password 칼럼의 값을 가져옴
        if(password_verify($password, $db_password)){
            // 사용자가 존재하지 않음
            echo json_encode(["success" => true, "message" => "로그인에 성공했습니다"]);
            }else{
            // 사용자가 존재하지 않음
            echo json_encode(["success" => false, "message" => "로그인에 실패했습니다"]);
            }
    }else{
        // 사용자가 존재하지 않음
    echo json_encode(["success" => false, "message" => "로그인에 실패했습니다"]);
    }
    
}else{
    // 사용자가 존재하지 않음
    echo json_encode(["success" => false, "message" => "로그인에 실패했습니다"]);
}
?>