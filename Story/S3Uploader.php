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
    public function uploadSingle($key, $filePath, $contentType){
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

            // 업로드 성공 시 파일의 URL 반환
            return ['success' => true, 'url' => $result['ObjectURL']];

        } catch (S3Exception $e) {

           // 업로드 실패 시, 오류 메시지 로그에 기록
            error_log($e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
        
    }

    public function uploadSingleWtihTrack($key, $filePath, $contentType){
        try {
           // 파일을 읽어서 Body에 넣어 업로드
            $body = file_get_contents($filePath);

            // S3 버킷에 데이터 업로드
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => $body, // 직접 데이터를 Body에 넣어 업로드
                'ContentType' => $contentType, // 데이터 타입 지정
                '@http' => [
                    'progress' => function ($downloadTotalSize, $downloadSizeSoFar, $uploadTotalSize, $uploadSizeSoFar) {
                        if ($uploadTotalSize > 0) {  // To avoid division by zero
                            $percentComplete = ($uploadSizeSoFar / $uploadTotalSize) * 100;
                            error_log(sprintf("%.2f%% of %d bytes uploaded.", $percentComplete, $uploadTotalSize));
                        }
                    }
                ]
            ]);
              // 업로드 성공 시 파일의 URL 반환
              return ['success' => true, 'url' => $result['ObjectURL']];

        } catch (S3Exception  $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];

        }
    }
}
?>
