<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; // 데이터베이스 연결

// JSON 데이터 읽기
// 클라이언트가 JSON 형태로 데이터를 전송하므로, php://input 스트림을 사용하여 이 데이터를 읽어야 합니다. 
// $_POST 대신 아래와 같이 수정
$json = file_get_contents('php://input');
// if ($json) {
//     error_log("JSON data: " . $json);
// } else {
//     error_log("JSON empty");
// }
$data = json_decode($json, true); // true를 추가하여 배열로 변환

//data에서 각 키를 뽑아내기
$userId = $data['userId'];
$imageBase64 = $data['image'];

// 쿼리 작성
$sql = "UPDATE userProfile SET home_background_image = :image 
                                        WHERE user_id = :userId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':image', $imageBase64);
$stmt->bindParam(':userId', $userId);
$result = $stmt->execute();

// 결과 확인 및 출력
if ($result) {

    // rowCount()를 사용하여 업데이트된 행의 수 확인
    if ($stmt->rowCount() > 0) {

    echo json_encode(["success" => true, "message" => "저장 성공!"]);
    }
    
    else{
    echo json_encode(["success" => true, "message" => "업데이트 될 행이 존재하지 않습니다"]);
    }

} else {
    echo json_encode(["success" => false, "message" => "저장 실패"]);
}

?>