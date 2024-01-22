<?php
//에러 리포팅
ini_set('display_errors', 1);
error_reporting(E_ALL);

//include는 파일이 없는 경우에도 스크립트 실행을 계속하고, require는 파일이 없는 경우 스크립트 실행을 중단합니다.
require '../db_connect.php'; // 데이터베이스 연결

$email = $_POST['email'];// 클라이언트로부터 받은 이메일


?>