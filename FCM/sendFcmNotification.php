<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

function sendFcmNotification($device_token, $messageData){
    // 서버 키를 저장한 파일 경로
    $serverKeyFile = '/var/www/html/FCM/server_key.txt';
    // URL 및 서버 키 설정
    $url = 'https://fcm.googleapis.com/fcm/send';
    $server_key = file_get_contents($serverKeyFile);

    // 데이터 및 헤더 준비
    $fields = array(
        'to' => $device_token, // 'registration_ids' 대신 'to'를 사용하고, 배열 대신 문자열 값을 사용
        'data' => $messageData
    );
    $fields = json_encode($fields);

    $headers = array(
        'Content-Type:application/json',
        'Authorization:key='.$server_key
    );

    // cURL 사용
    // PHP의 cURL 라이브러리를 사용하여 FCM 서버에 POST 요청을 보냅니다
    // 요청은 json_encode로 인코딩된 $fields를 바디로 포함합니다.
    $ch =curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    $result = curl_exec($ch);

    $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // HTTP 상태 코드 가져오기

    if ($result === FALSE) {
         // cURL 실행에 실패했을 경우
         $errorMessage = 'Oops! FCM Send Error: ' . curl_error($ch);
         error_log($errorMessage); // 에러 로그 기록
         die($errorMessage);
         return false;
    }elseif($httpStatusCode == 200){
         // 요청이 성공적으로 처리되었을 경우
        return true;
    }else {
        // 그 외의 경우, 요청은 처리되었으나 성공적이지 않은 경우
        $errorMessage = 'FCM Send Error: HTTP status code ' . $httpStatusCode;
        error_log($errorMessage); // 에러 로그 기록
        return false;
    }

    curl_close($ch);
    }

?>