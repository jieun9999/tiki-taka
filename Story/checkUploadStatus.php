<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; 

// 스토리 폴더 1개 가져오기
$sql = "SELECT * FROM storyFolder WHERE folder_id = :folderId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':folderId', $folderId, PDO::PARAM_INT);
$stmt->execute();

// 클라이언트에게 JSON 형태로 응답
header('Content-Type: application/json');

// 결과를 연관배열로 받기
// StoryFolder 클래스가 PHP 서버 측에는 없는 경우, PHP에서 실행한 쿼리의 결과를 연관 배열 형태로 받아와 JSON 형식으로 인코딩하여 안드로이드에 전송
$storyFolder = $stmt->fetch(PDO::FETCH_ASSOC);


//결과에 따라 다른 응답 출력
if($storyFolder){

    // 결과가 있는 경우
    echo json_encode(["success" => true, "storyFolder" => $storyFolder, "message" =>"썸네일 가져오기 성공" ]);

}else{
    
    // 결과가 없는 경우
    echo json_encode(["success" => false, "message" =>"썸네일을 찾을 수 없습니다."]);
}

?>