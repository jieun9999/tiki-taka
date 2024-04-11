<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결
$userId = $_GET['userId']; // 클라이언트에서 받은 userId
$partnerId = $_GET['partnerId'];
// error_log("story data: " . $userId);

// 스토리 폴더 리스트 가져오기
$sql = "SELECT * FROM storyFolder WHERE user_id = :userId OR user_id = :partnerId
        ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->bindParam(':partnerId', $partnerId, PDO::PARAM_INT);
$stmt->execute();

// 클라이언트에게 JSON 형태로 응답
header('Content-Type: application/json');

// 결과를 배열로 반환
// PDOStatement 객체의 fetchAll 메서드를 호출하는 것으로, 쿼리 결과로 반환된 모든 행을 배열로 가져옴
// 각각의 행은 컬럼 이름을 키로 하는 배열이 됨
$storyFolders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 배열을 문자열로 변환하여 로그로 남김
// error_log(print_r($storyFolders, true));

//결과에 따라 다른 응답 출력
if(empty($storyFolders)){

    // 결과가 없는 경우
    echo json_encode(["success" => false, "message" =>"스토리폴더가 없습니다."]);

}else{
    // 결과가 있는 경우
    echo json_encode(["success" => true,"storyFolders" => $storyFolders, "message" =>"스토리폴더 가져오기 성공" ]);
}


?>