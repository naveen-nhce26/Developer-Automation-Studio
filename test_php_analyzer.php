<?php

require_once __DIR__ . '/analyzers/PhpAnalyzer.php';

$sampleCode = <<<'PHP'
<?php

trait LoggerTrait
{
    public function logMessage($message)
    {
        echo $message;
    }
}

interface PaymentInterface
{
    public function pay($amount);
}

class PaymentService implements PaymentInterface
{
    use LoggerTrait;

    public function pay($amount)
    {
        exec('whoami');
        return $amount;
    }
}

class RefundService extends PaymentService
{
    public function refund($amount)
    {
        shell_exec('dir');
        return $amount;
    }
}

function calculateTax($amount)
{
    return $amount * 0.18;
}

require_once 'config.php';
include 'database.php';
include_once 'helpers.php';
require 'settings.php';

PHP;

$analyzer = new PhpAnalyzer();

$result = $analyzer->analyze($sampleCode);

echo "<pre>";
print_r($result);
echo "</pre>";