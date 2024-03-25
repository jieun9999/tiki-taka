<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php';

try{

    // JSON 데이터를 PHP에서 받기
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    //PHP 코드에서 이 객체의 속성에 접근할 때는 JSON 데이터의 키 이름을 그대로 사용
    $dateMarker = $data -> date_marker;
    $content = $data -> content;
    $chatRoomId = $data -> room_id;

    // 댓글 INSERT 쿼리 준비 및 실행
    $sql = "INSERT INTO message (content, date_marker, room_id) VALUES (:content, :date_marker, :room_id)";
    $stmt = $conn -> prepare($sql);
    $success = $stmt -> execute([':content' => $content, ':date_marker' => $dateMarker, ':room_id' => $chatRoomId]);


    if($success){
        //성공응답
        echo json_encode(["success" => true, "message" => "게시 성공!"]);
    }else{
        // 실패 응답
        echo json_encode(["success" => false, "message" => "쿼리 실패"]);
    }

    }catch(PDOException $e) {

    // 실패 응답
    echo json_encode(["success" => false, "message" => "게시 실패 ㅠ: " . $e->getMessage()]);

    }

?>