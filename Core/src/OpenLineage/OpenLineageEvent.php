<?php
    namespace Core\OpenLineage;
    
    use Ramsey\Uuid\Uuid;

    class OpenLineageEvent implements \JsonSerializable {

        private const UNKNOWN_COLUMN_PLACEHOLDER = "UNKNOWN";

        private readonly string $eventTime;
        private readonly string $jobName;
        private readonly string $coreBaseUrl;
        
        private array $inputs;
        private array $outputs;

        public function __construct(string $eventTime, string $jobName, array $inputs, array $outputs, string $coreBaseUrl) {
            $this->eventTime = $eventTime;
            $this->jobName = $jobName;
            $this->inputs = $inputs;
            $this->outputs = $outputs;
            $this->coreBaseUrl = $coreBaseUrl;
        }

        public function addInput(string $namespace, string $name, array $hierarchy, mixed $data) : void {
            if (!array_filter($this->inputs, fn($dataset) => $dataset["namespace"] === $namespace && $dataset["name"] === $name)) {
                $this->inputs[] = self::createDataset($namespace, $name, $hierarchy, $data);
            }
        }

        public function addOutput(string $namespace, string $name, array $hierarchy, mixed $data) : void {
            if (!array_filter($this->outputs, fn($dataset) => $dataset["namespace"] === $namespace && $dataset["name"] === $name)) {
                $this->outputs[] = self::createDataset($namespace, $name, $hierarchy, $data);
            }
        }

        public function shouldBePublished() {
            return count($this->outputs) > 0;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return array(
                "eventType" => "COMPLETE",
                "eventTime" => $this->eventTime,
                "run" => array(
                    "runId" => Uuid::uuid4()->toString()
                ),
                "job" => array(
                    "namespace" => $this->coreBaseUrl,
                    "name" => $this->jobName
                ),
                "inputs" => $this->inputs,
                "outputs" => $this->outputs,
                "producer" => $this->coreBaseUrl,
                "schemaURL" => "https://openlineage.io/spec/1-0-2/OpenLineage.json"
            );
        }

        private static function createDataset(string $namespace, string $name, array $hierarchy, mixed $data) : mixed {
            $convertedData = json_decode(json_encode($data), true);
            $columns = is_array($convertedData) && (empty($convertedData)
                || array_keys($convertedData) !== range(0, count($convertedData) - 1))
                ? array_keys($convertedData) : array(self::UNKNOWN_COLUMN_PLACEHOLDER);
            return array(
                "namespace" => $namespace,
                "name" => $name,
                "facets" => array(
                    "schema" => array(
                        "fields" => array_map(fn($column) => array("name" => $column), $columns)
                    ),
                    "hierarchy" => array(
                        "hierarchy" => $hierarchy
                    )
                )
            );
        }
    }
?>