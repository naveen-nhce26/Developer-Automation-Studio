
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class PhpAnalyzerVisitor extends NodeVisitorAbstract
{
    private array $results = [
        'classes' => [],
        'interfaces' => [],
        'traits' => [],
        'functions' => [],
        'methods' => [],
        'variables' => [],
        'dependencies' => [],
        'risky_functions' => []
    ];

    public function enterNode(Node $node)
    {
        // Detect classes
        if ($node instanceof Node\Stmt\Class_) {
            $this->results['classes'][] =
                $node->name?->toString() ?? 'Anonymous class';
        }

        // Detect interfaces
        if ($node instanceof Node\Stmt\Interface_) {
            $this->results['interfaces'][] =
                $node->name->toString();
        }

        // Detect traits
        if ($node instanceof Node\Stmt\Trait_) {
            $this->results['traits'][] =
                $node->name->toString();
        }

        // Detect functions
        if ($node instanceof Node\Stmt\Function_) {
            $this->results['functions'][] =
                $node->name->toString();
        }

        // Detect class methods
        if ($node instanceof Node\Stmt\ClassMethod) {
            $this->results['methods'][] =
                $node->name->toString();
        }

        // Detect variables
        if (
            $node instanceof Node\Expr\Variable &&
            is_string($node->name)
        ) {
            $this->results['variables'][] = $node->name;
        }

        // Detect include and require statements
        if ($node instanceof Node\Expr\Include_) {
            $this->results['dependencies'][] = [
                'type' => $this->getIncludeType($node->type),
                'line' => $node->getStartLine()
            ];
        }

        // Detect eval() - it is a PHP language construct
        if ($node instanceof Node\Expr\Eval_) {
            $this->results['risky_functions'][] = [
                'function' => 'eval',
                'line' => $node->getStartLine()
            ];
        }

        // Detect potentially risky function calls
        if (
            $node instanceof Node\Expr\FuncCall &&
            $node->name instanceof Node\Name
        ) {
            $functionName = strtolower(
                $node->name->toString()
            );

            $riskyFunctions = [
                'exec',
                'shell_exec',
                'system',
                'passthru',
                'proc_open',
                'popen'
            ];

            if (in_array($functionName, $riskyFunctions, true)) {
                $this->results['risky_functions'][] = [
                    'function' => $functionName,
                    'line' => $node->getStartLine()
                ];
            }
        }
    }

    private function getIncludeType(int $type): string
    {
        return match ($type) {
            Node\Expr\Include_::TYPE_INCLUDE => 'include',
            Node\Expr\Include_::TYPE_INCLUDE_ONCE => 'include_once',
            Node\Expr\Include_::TYPE_REQUIRE => 'require',
            Node\Expr\Include_::TYPE_REQUIRE_ONCE => 'require_once',
            default => 'unknown'
        };
    }

    public function getResults(): array
    {
        // Remove duplicate variable names
        $this->results['variables'] = array_values(
            array_unique($this->results['variables'])
        );

        return $this->results;
    }
}

class PhpAnalyzer
{
    public function analyze(string $content): array
    {
        $parser = (new ParserFactory())
            ->createForNewestSupportedVersion();

        $totalLines = $content === ''
            ? 0
            : count(preg_split('/\r\n|\r|\n/', $content));

        try {
            // Parse PHP source without executing it
            $ast = $parser->parse($content);

            $visitor = new PhpAnalyzerVisitor();

            $traverser = new NodeTraverser();
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast ?? []);

            $results = $visitor->getResults();

            return [
                'status' => 'SUCCESS',
                'syntax_valid' => true,
                'total_lines' => $totalLines,

                'total_classes' => count($results['classes']),
                'total_interfaces' => count($results['interfaces']),
                'total_traits' => count($results['traits']),
                'total_functions' => count($results['functions']),
                'total_methods' => count($results['methods']),
                'total_variables' => count($results['variables']),

                'classes' => $results['classes'],
                'interfaces' => $results['interfaces'],
                'traits' => $results['traits'],
                'functions' => $results['functions'],
                'methods' => $results['methods'],
                'variables' => $results['variables'],

                'dependencies' => $results['dependencies'],
                'risky_functions' => $results['risky_functions'],

                'summary' => 'PHP file analyzed successfully. Found '
                    . count($results['classes']) . ' classes, '
                    . count($results['functions']) . ' functions and '
                    . count($results['methods']) . ' methods.'
            ];

        } catch (Error $e) {
            return [
                'status' => 'FAILED',
                'syntax_valid' => false,
                'total_lines' => $totalLines,
                'error' => $e->getMessage(),
                'error_line' => $e->getStartLine(),
                'summary' => 'PHP syntax validation failed.'
            ];
        }
    }
}
