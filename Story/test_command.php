<?php
//시스템 명령 실행 권한 확인
exec("echo Hello World", $output, $returnVar);
if ($returnVar == 0) {
    echo "PHP can execute system commands. Output: " . implode("\n", $output); //PHP can execute system commands.  출력됨
} else {
    echo "PHP cannot execute system commands.";
}
?>
