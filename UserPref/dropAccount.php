<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결

$userId = $_GET['userId'];
error_log("data: " . $userId);

// mysql에서는 하나의 쿼리 내에서 여러개의 테이블을 업데이트하는 것을 지원X
// 2개의 업데이트문을 하나의 트랜잭션으로 묶어 실행
// 2개의 쿼리가 모두 성공적으로 실행되거나, 하나라도 실패할 경우 모든 변경사항이 롤백되어 데이터 일관성이 유지됨

header('Content-Type: application/json');

try{
    //트랜잭션 시작
    $conn -> beginTransaction();

     // userAuth 테이블 업데이트
     $stmt1 = $conn->prepare("UPDATE userAuth SET email = NULL, password = NULL, auth_code = NULL, auth_code_date = NULL, invite_code = NULL, invite_code_date = NULL, partner_id = NULL, connect = NULL, temporary_password = NULL, temporary_password_date = NULL, disconnect_date = NULL WHERE user_id = :userId");
     $stmt1->bindParam(':userId', $userId, PDO::PARAM_INT);
     $stmt1->execute();
 
     // userProfile 테이블 업데이트
     $stmt2 = $conn->prepare("UPDATE userProfile SET profile_image = NULL, gender = NULL, name = NULL, birthday = NULL, first_date = NULL, agree_app_terms = NULL, agree_privacy_policy = NULL, home_background_image = NULL, profile_background_image = NULL, profile_message = NULL WHERE user_id = :userId");
     $stmt2->bindParam(':userId', $userId, PDO::PARAM_INT);
     $stmt2->execute();

     //트랜잭션 커밋
     $conn -> commit();

     //성공응답
     echo json_encode(["success" => true, "message" => "계정이 삭제되었습니다."]);

}catch (Exception $e){
  
    // 오류 발생시 롤백
    $conn -> rollBack();

    // 실패 응답
    echo json_encode(["success" => false, "message" => "계정 삭제 실패: " . $e->getMessage()]);
}


?>