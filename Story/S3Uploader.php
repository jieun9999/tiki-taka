<?php
require '../aws/aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class S3Uploader{
    private $s3Client;
    private $bucket;

    public function __construct($accessKey, $secretKey, $region, $bucket){
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => $region,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
            'use_accelerate_endpoint' => true
        ]);
        $this->bucket = $bucket;
    }

    public function upload($key, $filePath, $contentType){
        try {
            // 파일을 읽어서 Body에 넣어 업로드
            $body = file_get_contents($filePath);

            // S3 버킷에 데이터 업로드
            $result = $this ->s3Client->putObject([
                'Bucket' => $this -> bucket,
                'Key'    => $key,
                'Body'   => $body, // 직접 데이터를 Body에 넣어 업로드
                'ContentType' => $contentType, // 데이터 타입 지정
            ]);
        
            // 업로드 성공 시, 결과 반환
            return ['success' => true, 'url' => $result['ObjectURL']];
            // 이미지와 동영상 파일 모두 url을 반환받음

        } catch (S3Exception $e) {

           // 업로드 실패 시, 오류 메시지 로그에 기록
            error_log($e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
        
    }
}

?>