<?php
require '../aws/aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;

class S3Uploader{
    private $s3Client;
    private $bucket;
    private $totalSize;

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

            // 업로드 성공 시, 결과 반환
            return ['success' => true, 'url' => $result['ObjectURL']];
            // 이미지와 동영상 파일 모두 url을 반환받음

        } catch (S3Exception $e) {

           // 업로드 실패 시, 오류 메시지 로그에 기록
            error_log($e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
        
    }

    // 동영상 파일 같이 용량이 큰 경우에는 메모리가 한번에 많이 사용되므로,
    // 단일 객체 업로드가 아닌 멀티 파트 업로드로 전송함
    public function uploadMulti($key, $filePath, $contentType, $totalSize){
        $this->totalSize = $totalSize;  // 전체 파일 크기 설정

        try {
            // S3 버킷에 데이터 업로드
            // 각 부분은 별도로 업로드되며, 모든 부분이 성공적으로 업로드된 후에 자동으로 하나의 객체로 결합됩니다.
            // AWS의 최소 조각 크기인 5MB를 기본으로 사용
            $uploader = new MultipartUploader($this ->s3Client, $filePath, [
                'Bucket' => $this -> bucket,
                'Key'    => $key,
                'ContentType' => $contentType, // 데이터 타입 지정
                // Apply the http option to a specific command using the "@http"
                'params' => [
                    '@http' => [
                        'progress' => function ($downloadTotalSize, $downloadSizeSoFar, $uploadTotalSize, $uploadSizeSoFar) {
                            if ($this->totalSize > 0) {  
                                $percent = ($uploadSizeSoFar / $this->totalSize) * 100; // 현재 HTTP 요청에서 전송된 크기를 기준으로 백분율 계산
                                error_log(sprintf("%.2f%% of %s bytes uploaded.", $percent, $this->totalSize));
                            } else {
                                error_log("Upload size is determined to be zero.");  // 업로드 크기가 0인 경우의 처리
                            }
                        }
                    ]
                ]
            ]);
        
            $result = $uploader -> upload();
            // 업로드 성공 시, 결과 반환
            return ['success' => true, 'url' => $result['ObjectURL']];
            // 이미지와 동영상 파일 모두 url을 반환받음

        } catch (MultipartUploadException $e) {

           // 업로드 실패 시, 오류 메시지 로그에 기록
            error_log($e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    
    }


    
}

?>