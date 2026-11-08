
<?php

class JsonAnalyzer
{
    public function analyze(string $content): array
    {
        $totalLines = $content === ''
            ? 0
            : count(preg_split('/\r\n|\r|\n/', $content));

        try {
            // Decode JSON without converting objects into arrays.
            $data = json_decode(
                $content,
                false,
                512,
                JSON_THROW_ON_ERROR
            );

            // Initialize counters.
            $counts = [
                'objects' => 0,
                'arrays' => 0,
                'properties' => 0,
                'strings' => 0,
                'numbers' => 0,
                'booleans' => 0,
                'nulls' => 0
            ];

            // Recursively analyze the decoded JSON structure.
            $this->analyzeValue($data, $counts);

            $totalValues =
                $counts['strings']
                + $counts['numbers']
                + $counts['booleans']
                + $counts['nulls'];

            return [
                'status' => 'SUCCESS',
                'syntax_valid' => true,
                'total_lines' => $totalLines,

                'total_objects' => $counts['objects'],
                'total_arrays' => $counts['arrays'],
                'total_properties' => $counts['properties'],
                'total_strings' => $counts['strings'],
                'total_numbers' => $counts['numbers'],
                'total_booleans' => $counts['booleans'],
                'total_nulls' => $counts['nulls'],
                'total_values' => $totalValues,

                'summary' => 'JSON file analyzed successfully. Found '
                    . $counts['objects'] . ' objects, '
                    . $counts['arrays'] . ' arrays and '
                    . $counts['properties'] . ' properties.'
            ];

        } catch (JsonException $e) {

            return [
                'status' => 'FAILED',
                'syntax_valid' => false,
                'total_lines' => $totalLines,
                'error' => $e->getMessage(),
                'summary' => 'JSON syntax validation failed.'
            ];
        }
    }

    private function analyzeValue($value, array &$counts): void
    {
        // Detect JSON objects.
        if ($value instanceof stdClass) {

            $counts['objects']++;

            foreach ($value as $propertyValue) {

                $counts['properties']++;

                $this->analyzeValue(
                    $propertyValue,
                    $counts
                );
            }

            return;
        }

        // Detect JSON arrays.
        if (is_array($value)) {

            $counts['arrays']++;

            foreach ($value as $item) {

                $this->analyzeValue(
                    $item,
                    $counts
                );
            }

            return;
        }

        // Detect strings.
        if (is_string($value)) {
            $counts['strings']++;
            return;
        }

        // Detect numbers.
        if (is_int($value) || is_float($value)) {
            $counts['numbers']++;
            return;
        }

        // Detect boolean values.
        if (is_bool($value)) {
            $counts['booleans']++;
            return;
        }

        // Detect null values.
        if ($value === null) {
            $counts['nulls']++;
        }
    }
}
