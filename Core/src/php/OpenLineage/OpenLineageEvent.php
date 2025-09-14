<?php
    namespace Core\OpenLineage;

    class OpenLineageEvent implements \JsonSerializable {

        private const UNKNOWN_COLUMN_PLACEHOLDER = "UNKNOWN";

        private readonly string $eventTime;
        private readonly string $jobName;
        private array $inputs;
        private array $outputs;

        public function __construct(string $eventTime, string $jobName, array $inputs, array $outputs) {
            $this->eventTime = $eventTime;
            $this->jobName = $jobName;
            $this->inputs = $inputs;
            $this->outputs = $outputs;
        }

        public function addInput(string $namespace, string $name, mixed $data) : void {
            if (!array_filter($this->inputs, fn($ds) => $ds["namespace"] === $namespace && $ds["name"] === $name)) {
                $this->inputs[] = self::createDataset($namespace, $name, $data);
            }
        }

        public function addOutput(string $namespace, string $name, mixed $data) : void {
            if (!array_filter($this->outputs, fn($ds) => $ds["namespace"] === $namespace && $ds["name"] === $name)) {
                $this->outputs[] = self::createDataset($namespace, $name, $data);
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
                    "runId" => self::uuid()
                ),
                "job" => array(
                    "namespace" => BASE_URL,
                    "name" => $this->jobName
                ),
                "inputs" => $this->inputs,
                "outputs" => $this->outputs,
                "producer" => BASE_URL . "/openlineage",
                "schemaURL" => "https://openlineage.io/spec/1-0-2/OpenLineage.json"
            );
        }

        private static function createDataset(string $namespace, string $name, mixed $data) : mixed {
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
                    )
                )
            );
        }

        private static function uuid() {
            $data = random_bytes(16);
            $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
            $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
            return vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex($data), 4));
        }
    }
?>