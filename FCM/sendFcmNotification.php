<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

function sendFcmNotification($device_token, $messageData){

    // FCM v1 버전으로 업그레이드 되면서 달라짐
    // 기존에 요청을 날릴때는 Firebase Project에 있는 서버키 문자열을 복사해서 사용을 했습니다.
    // 하지만, v1 으로 변경되면서 추가적인 작업이 생겼습니다. 프로젝트의 json 파일을 받아서, 서버측에서 google cloud api를 한번 거쳐서, 엑세스 토큰을 받아서, 이 토큰으로 요청을 날려야 합니다.
    // 값을 전달할때도 기존에는 key ='서버키' 형태로 날렸는데, v1에서는 bearer "인증토큰" 형태로 날려야 합니다.

    // 해당파일은 google 인증을 사용하여 받은 엑세스 토큰 문자열을 반환하는 코드입니다.
    $url = 'https://fcm.googleapis.com/v1/projects/tiki-taka-22f76/messages:send';
    require_once ('/var/www/html/vendor/autoload.php');
    putenv('GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/FCM/tiki-taka-22f76-firebase-adminsdk-umsvj-47a382ccb8.json');
    $scope = 'https://www.googleapis.com/auth/firebase.messaging';
    $client = new Google_Client();
    $client->useApplicationDefaultCredentials();
    $client->setScopes($scope);

    $auth_key = $client->fetchAccessTokenWithAssertion();
    // error_log("Access Token: " . $auth_key['access_token']); // Access Token 로그 기록
    // echo $auth_key['access_token'];
    // 주석해제 하면 'http://52.79.41.79/send_fcm.php' 경로에서  ya29. 으로 시작하는 토큰값이 보입니다.

    // 헤더 설정
    $headers = array
    (
        'Authorization: Bearer ' . $auth_key['access_token'],
        'Content-Type: application/json'
    );

    // FCM 메시지 데이터 준비
    // error_log("device_token" . $device_token);
    $message = [
        'message' => [ // 최상위에 단일 message 객체
            'token' => $device_token, // 기기 토큰
            'notification' => [
                'title' => $messageData['title'], // 알림 제목
                'body' => $messageData['body'],   // 알림 내용
            ],
            'data' => [
                'flag' => $messageData['flag'],           // 알림 플래그
                'userProfile' => $messageData['userProfile'], // 사용자 프로필 이미지 URL
                'folderId' => (string) $messageData['folderId'], // 폴더 ID (문자열로 변환)
            ],
        ],
    ];
    
    $fields = json_encode($message);
    // error_log("fields" . $fields);
    // fields{"message":{"token":"dV_BGpBPRyKFzc-tPTVuOf:APA91bGOOv2neRinrh_Zh21rR8UUZ8NzUJtOWIufnBa14ZvnRRgIgLkcxcKk9bHQ2PhloRhOgXvnFZevPByy8d8BUu6HZcXp2tsohd26NedezGa12AfQDb0RpexdIkMYQPx2SYepXWQJ","notification":{"title":"tiki taka","body":"\\uc544\\uae30\\uc624\\ub9ac\\ub2d8\\uc774 1 \\uac1c\\uc758 \\uc0ac\\uc9c4\\uc744 \\ucd94\\uac00\\ud588\\uc2b5\\ub2c8\\ub2e4. \\ud655\\uc778\\ud574\\ubcf4\\uc138\\uc694!"},"data":{"flag":"story_image_notification","userProfile":"https:\\/\\/jieun-s3-bucket.s3.ap-northeast-2.amazonaws.com\\/profile\\/2025\\/02\\/01\\/321a8da7f6be38a591b6cdf8d2e9da88.jpg","folderId":"310"}}}


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