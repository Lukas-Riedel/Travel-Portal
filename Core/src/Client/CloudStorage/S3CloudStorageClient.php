<?php
    namespace Core\Client\CloudStorage;

    use Aws\S3\S3Client;

    class S3CloudStorageClient implements CloudStorageClient {

        private const BUCKET_POLICY_VERSION = "2012-10-17";

        private readonly string $region;
        private readonly string $host;
        private readonly string $port;
        private readonly string $accesskey;
        private readonly string $secretkey;
        private readonly string $s3BaseUrl;

        private ?S3Client $s3Client;

        public function __construct(string $region, string $host, string $port, string $accesskey, string $secretkey, string $s3BaseUrl) {
            $this->region = $region;
            $this->host = $host;
            $this->port = $port;
            $this->accesskey = $accesskey;
            $this->secretkey = $secretkey;
            $this->s3BaseUrl = $s3BaseUrl;
            $this->s3Client = null;
        }

        public function list(string $bucket) : array {
            $this->init();

            $keys = array();

            if (!$this->s3Client->doesBucketExist($bucket)) {
                return $keys;
            }

            try {
                $results = $this->s3Client->getPaginator("ListObjectsV2", array(
                    "Bucket" => $bucket
                ));

                foreach ($results as $result) {
                    if ($result["Contents"]) {
                        foreach ($result["Contents"] as $object) {
                            $keys[] = $object["Key"];
                        }
                    }
                }
            }
            catch (\Exception $e) {
                return array();
            }

            return $keys;
        }

        public function put(string $bucket, string $key, string $body) : void {
            $this->init();

            if (!$this->s3Client->doesBucketExist($bucket)) {
                $this->s3Client->createBucket(array(
                    "Bucket" => $bucket
                ));

                $policy = array(
                    "Version" => self::BUCKET_POLICY_VERSION,
                    "Statement" => array(array(
                        "Effect" => "Allow",
                        "Principal" => array("AWS" => array("*")),
                        "Action" => array("s3:GetObject"),
                        "Resource" => array("arn:aws:s3:::$bucket/*")
                    ))
                );

                $this->s3Client->putBucketPolicy(array(
                    "Bucket" => $bucket,
                    "Policy" => json_encode($policy),
                ));
            }

            $this->s3Client->putObject(array(
                "Bucket" => $bucket,
                "Key" => $key,
                "Body" => $body
            ));
        }

        public function delete(string $bucket, string $key) : void {
            $this->init();

            $this->s3Client->deleteObject(array(
                "Bucket" => $bucket,
                "Key" => $key
            ));
        }

        public function getPath(string $bucket, string $key) : string {
            return $this->s3BaseUrl . "/" . $bucket . "/" . $key;
        }

        private function init() : void {
            if ($this->s3Client === null) {
                $this->s3Client = new S3Client(array(
                    "version" => "latest",
                    "region" => $this->region,
                    "endpoint" => "http://" . $this->host . ":" . $this->port,
                    "use_path_style_endpoint" => true,
                    "credentials" => array(
                        "key" => $this->accesskey,
                        "secret" => $this->secretkey
                    )
                ));
            }
        }
    }
?>