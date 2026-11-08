
<?php

class LogAnalyzer
{
    public function analyze(string $content): array
    {
        // Split content into individual lines
        $content = trim($content);

        $lines = $content === ''
            ? []
            : preg_split('/\r\n|\r|\n/', $content);

        $totalLines = count($lines);

        // Initialize log-level counters
        $levels = [
            'INFO' => 0,
            'WARNING' => 0,
            'ERROR' => 0,
            'DEBUG' => 0
        ];

        $timestamps = [];
        $errorMessages = [];

        // Common timestamp pattern:
        // 2026-11-08 10:15:20
        // 2026-11-08T10:15:20
        // 2026-11-08 10:15:20,123
        // 2026-11-08T10:15:20.123Z
        $timestampPattern =
            '/\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}'
            . '(?:[.,]\d+)?'
            . '(?:Z|[+-]\d{2}:?\d{2})?/';

        // Recognized log levels
        $levelPattern = '(INFO|WARNING|WARN|ERROR|DEBUG)';

        foreach ($lines as $line) {

            $level = null;
            $message = '';

            // Extract timestamp independently
            if (preg_match(
                $timestampPattern,
                $line,
                $timestampMatches
            )) {
                $timestamps[] = $timestampMatches[0];
            }

            /*
             * Supported formats:
             *
             * 1. 2026-11-08 10:15:20 ERROR Application started
             *
             * 2. [2026-11-08 10:15:20] [INFO] Application started
             *
             * 3. [INFO] Application started
             *
             * 4. 2026-11-08 10:15:20,123 - ERROR - Payment failed
             *
             * 5. 2026-11-08 10:15:20 ERROR [PaymentService] Payment failed
             *
             * Ordinary sentences containing ERROR or INFO
             * are intentionally ignored.
             */

            // Format 1, 2 and 5:
            // Timestamp followed by log level
            $timestampedPattern =
                '/^\s*\[?'
                . '\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}'
                . '(?:[.,]\d+)?'
                . '(?:Z|[+-]\d{2}:?\d{2})?'
                . '\]?\s+'
                . '\[?' . $levelPattern . '\]?'
                . '\s*[:\-]?\s*(.*)$/i';

            // Format 3:
            // [INFO] Application started
            $bracketedPattern =
                '/^\s*\[' . $levelPattern . '\]'
                . '\s*[:\-]?\s*(.*)$/i';

            // Format 4:
            // 2026-11-08 10:15:20,123 - ERROR - Payment failed
            $pythonPattern =
                '/^\s*'
                . '\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}'
                . '(?:[.,]\d+)?'
                . '(?:Z|[+-]\d{2}:?\d{2})?'
                . '\s+-\s+'
                . $levelPattern
                . '\s+-\s*(.*)$/i';

            // Check timestamped format first
            if (preg_match(
                $timestampedPattern,
                $line,
                $matches
            )) {

                $level = strtoupper($matches[1]);
                $message = trim($matches[2]);

            }
            // Check Python-style format
            elseif (preg_match(
                $pythonPattern,
                $line,
                $matches
            )) {

                $level = strtoupper($matches[1]);
                $message = trim($matches[2]);

            }
            // Check bracketed format
            elseif (preg_match(
                $bracketedPattern,
                $line,
                $matches
            )) {

                $level = strtoupper($matches[1]);
                $message = trim($matches[2]);

            }

            // Process recognized log entries only
            if ($level !== null) {

                // Normalize WARN to WARNING
                if ($level === 'WARN') {
                    $level = 'WARNING';
                }

                $levels[$level]++;

                // Collect error messages
                if ($level === 'ERROR' && $message !== '') {
                    $errorMessages[] = $message;
                }
            }
        }

        // Identify repeated error messages
        $repeatedErrors = array_filter(
            array_count_values($errorMessages),
            fn($count) => $count > 1
        );

        return [
            'total_lines' => $totalLines,
            'log_levels' => $levels,
            'timestamps' => $timestamps,
            'total_errors' => $levels['ERROR'],
            'repeated_errors' => $repeatedErrors
        ];
    }
}
