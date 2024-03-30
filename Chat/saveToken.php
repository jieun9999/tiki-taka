<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

try{

    // JSON 데이터를 PHP에서 받기
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    //PHP 코드에서 이 객체의 속성에 접근할 때는 JSON 데이터의 키 이름을 그대로 사용
    $userId = $data -> user_id;
    $token = $data -> token;

    // 댓글 INSERT 쿼리 준비 및 실행
    $sql = "INSERT INTO fcmToken (user_id, token) VALUES (:user_id, :token)";
    $stmt = $conn -> prepare($sql);
    $success = $stmt -> execute([':user_id' => $userId, ':token' => $token]);


    if($success){
        //성공 응답
        echo json_encode(['success' => true]);
    }else{
        // 실패 응답
        echo json_encode(['success' => false]);
    }

    }catch(PDOException $e) {

    // 오류 처리
    error_log("Database error: " . $e->getMessage());
    }

?>