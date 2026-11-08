
<?php

class TextAnalyzer
{
    public function analyze(string $content): array
    {
        // Count characters, including spaces and line breaks
        $totalCharacters = mb_strlen($content, 'UTF-8');

        // Split content into lines
        $lines = preg_split('/\r\n|\r|\n/', $content);

        $totalLines = count($lines);

        // Count non-empty lines
        $nonEmptyLines = 0;
        $emptyLines = 0;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                $emptyLines++;
            } else {
                $nonEmptyLines++;
            }
        }

        // Count words
        $words = preg_split('/\s+/u', trim($content), -1, PREG_SPLIT_NO_EMPTY);

        $totalWords = count($words);

        // Count paragraphs separated by one or more blank lines
        $paragraphs = preg_split('/(?:\r\n|\r|\n)\s*(?:\r\n|\r|\n)/', trim($content), -1, PREG_SPLIT_NO_EMPTY);

        $totalParagraphs = count($paragraphs);

        // Generate summary
        $summary = "Text file contains "
            . $totalLines . " lines, "
            . $totalWords . " words, "
            . $totalCharacters . " characters and "
            . $totalParagraphs . " paragraphs.";

        return [
            'total_lines' => $totalLines,
            'total_words' => $totalWords,
            'total_characters' => $totalCharacters,
            'total_paragraphs' => $totalParagraphs,
            'non_empty_lines' => $nonEmptyLines,
            'empty_lines' => $emptyLines,
            'summary' => $summary
        ];
    }
}
