
<?php

class InputDetector
{
    public function detect(string $content, ?string $fileName = null): array
    {
        $trimmedContent = trim($content);

        // 1. Detect empty content
        if ($trimmedContent === '') {
            return [
                'type' => 'UNKNOWN',
                'confidence' => 'NONE',
                'reason' => 'The supplied content is empty.'
            ];
        }

        // 2. Detect PHP source code
        if (
            preg_match('/^<\?(?:php\b|=)/i', $trimmedContent)
        ) {
            return [
                'type' => 'PHP',
                'confidence' => 'HIGH',
                'reason' => 'PHP opening tag detected.'
            ];
        }

        // 3. Detect JSON objects and arrays
        // Invalid JSON is also sent to JsonAnalyzer for validation.
        if (
            str_starts_with($trimmedContent, '{') ||
            str_starts_with($trimmedContent, '[')
        ) {
            return [
                'type' => 'JSON',
                'confidence' => 'HIGH',
                'reason' => 'JSON object or array structure detected.'
            ];
        }

        // 4. Detect structured log patterns
        $logPatterns = [
            '/\b(INFO|ERROR|WARNING|WARN|DEBUG|CRITICAL)\b/i',
            '/^\s*\[[^\]]+\]\s*(INFO|ERROR|WARNING|WARN|DEBUG)\b/im',
            '/\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}:\d{2}/'
        ];

        foreach ($logPatterns as $pattern) {
            if (preg_match($pattern, $trimmedContent)) {
                return [
                    'type' => 'LOG',
                    'confidence' => 'MEDIUM',
                    'reason' => 'Structured log pattern detected.'
                ];
            }
        }

        // 5. Use file extension as fallback
        if ($fileName !== null) {

            $extension = strtolower(
                pathinfo($fileName, PATHINFO_EXTENSION)
            );

            $extensionTypes = [
                'log' => 'LOG',
                'txt' => 'TXT',
                'php' => 'PHP',
                'json' => 'JSON'
            ];

            if (isset($extensionTypes[$extension])) {
                return [
                    'type' => $extensionTypes[$extension],
                    'confidence' => 'MEDIUM',
                    'reason' => 'File extension used as a fallback.'
                ];
            }
        }

        // 6. Default to plain text
        return [
            'type' => 'TXT',
            'confidence' => 'LOW',
            'reason' => 'No specific supported format was detected. Treated as plain text.'
        ];
    }
}
