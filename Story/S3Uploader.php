<?php
require '../aws/aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class S3Uploader{
    private $s3Client;
    private $bucket;
    private $conn;

    public function __construct($accessKey, $secretKey, $region, $bucket, $conn){
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
        $this->conn = $conn; // 데이터베이스 연결 객체 저장
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
                    'progress' => function ($downloadTotalSize, $downloadSizeSoFar, $uploadTotalSize, $uploadSizeSoFar) use ($key) {
                        static $lastReportedProgress = -10; // Initialize to -10 so it starts reporting at the first 10% increment
                        if ($uploadTotalSize > 0) {  // To avoid division by zero
                            $percentComplete = floor($uploadSizeSoFar / $uploadTotalSize * 100);

                            // Check if the new percentComplete is at least 10% greater than the last reported progress
                            if ($percentComplete >= $lastReportedProgress + 10) {
                                $this->updateUploadStatus($key, $percentComplete);
                                error_log("percentComplete". $percentComplete);
                                $lastReportedProgress = $percentComplete; // Update the last reported progress
                            }
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

    public function updateUploadStatus($key, $progress){
      // realtime db를 짧은 시간내에 여러번 업데이트 한다
    }
}
?>
