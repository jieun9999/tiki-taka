<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../db_connect.php'; 

$userId = isset($_GET['userId']) ? $_GET['userId'] : null;

if($userId !== null){
      $sql = "SELECT COUNT(*) AS unread_messages FROM message WHERE sender_id != :userId AND is_read = 0";
      $stmt = $conn->prepare($sql);
      $stmt->execute([':userId' => $userId]);
      $result = $stmt->fetch(PDO::FETCH_ASSOC); 

      // 읽지 않은 메시지의 수에 따라 true 또는 false 출력
    if ($result['unread_messages'] > 0) {
        echo "false";
    } else {
        echo "true";
    }

}else{
    error_log("No userId provided in request");
}

?>