<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require '../db_connect.php'; // 데이터베이스 연결

$email = $_POST['email'];// 클라이언트로부터 받은 이메일

//1. 인증번호 생성
$verificationCode = rand(100000, 999999); // 6자리 랜덤 숫자 생성

//2. db에 이메일, 인증번호, 생성날짜를 저장함
$sql = "INSERT INTO userAuth (email, auth_code, auth_code_date) VALUES (:email, :code, NOW())";
$stmt = $conn-> prepare($sql);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->bindParam(':code', $verificationCode, PDO::PARAM_INT);
$insertResult = $stmt->execute();

// 3. 이메일 발송
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//실제 PHPMailer 라이브러리가 설치된 경로로 수정
require '../vendor/phpmailer/phpmailer/src/Exception.php';
require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';

$mail = new PHPMailer(true);

// "Simple Mail Transfer Protocol"의 약자로, 인터넷을 통한 이메일 전송에 사용되는 프로토콜
try {
    // 서버 설정
    $mail->isSMTP();                                      // SMTP를 사용
    $mail->Host = 'smtp.gmail.com';                       // 메일 서버 지정
    $mail->SMTPAuth = true;                               // SMTP 인증 사용
    $mail->Username = 'teseuteuyong51@gmail.com';             // 
    $mail->Password = 'shnr sfjk wfas xhcl';              // Gmail 앱비밀번호
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // TLS 암호화 사용
    $mail->Port = 587;                                    // TCP 포트 연결

    // 수신자 설정
    $mail->setFrom('teseuteuyong51@gmail.com', 'Mailer');     // 보내는 사람
    $mail->addAddress($email);     // 받는 사람

    // 컨텐츠 설정
    $mail->isHTML(true);                     // HTML 이메일 설정
    $mail->CharSet = 'UTF-8';                //한글 인코딩
    $mail->Subject = '이메일 인증 코드 발송';    // 이메일 제목
    $mail->Body = "<h1>이메일 인증 코드</h1>
               <p>귀하의 인증 코드: <strong>$verificationCode</strong></p>
               <p><strong>주의: 이 인증번호는 10분 내에 만료됩니다.</strong></p>";
               //html 메세지 본문


    // 이메일 전송
    $mail->send();
    $emailSent = true;

}catch (Exception $e) {
        // 오류 메시지를 로그 파일에 기록
        error_log('메일 전송 실패: ' . $mail->ErrorInfo); 
        $emailSent = false;
    }


// 최종결과 반환
// 서버의 응답을 `Boolean` 값으로 간단하게 처리
if ($insertResult && $emailSent) {
    echo json_encode(true); // 인증번호 전송 성공
} else {
    echo json_encode(false); // 인증번호 전송 실패
}

?>