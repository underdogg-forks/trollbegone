<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TestConventionsTest extends TestCase
{
    #[Test]
    public function it_enforces_test_method_naming_attributes_and_aaa_structure_across_the_suite(): void
    {
        $violations = [];

        foreach ($this->testFiles() as $file) {
            $content = file_get_contents($file);

            if ($content === false) {
                $violations[] = sprintf('%s: unable to read file', $file);
                continue;
            }

            preg_match_all('/(?:#\[Test\]\s*)?public function\s+([a-zA-Z0-9_]+)\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[1] as [$methodName, $nameOffset]) {
                if (! str_starts_with($methodName, 'it_')) {
                    $violations[] = sprintf('%s::%s must start with it_', $file, $methodName);
                }

                $signatureOffset = (int) $nameOffset;
                $beforeSignature = substr($content, 0, $signatureOffset);
                $lastAttributeOffset = strrpos($beforeSignature, '#[Test]');
                $lastClosingBrace = strrpos($beforeSignature, '}');

                if ($lastAttributeOffset === false || ($lastClosingBrace !== false && $lastAttributeOffset < $lastClosingBrace)) {
                    $violations[] = sprintf('%s::%s must be annotated with #[Test]', $file, $methodName);
                }

                $body = $this->methodBody($content, $methodName);
                if ($body === null) {
                    $violations[] = sprintf('%s::%s could not parse method body', $file, $methodName);
                    continue;
                }

                if (! $this->hasAaaSectionsInOrder($body)) {
                    $violations[] = sprintf('%s::%s must include Arrange, Act, Assert sections in order', $file, $methodName);
                }
            }
        }

        $this->assertSame([], $violations, "Test convention violations:\n- ".implode("\n- ", $violations));
    }

    /**
     * @return list<string>
     */
    private function testFiles(): array
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__), RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            if (! str_ends_with($path, 'Test.php')) {
                continue;
            }

            if (str_ends_with($path, 'TestConventionsTest.php')) {
                continue;
            }

            $paths[] = $path;
        }

        sort($paths);

        return $paths;
    }

    private function methodBody(string $content, string $methodName): ?string
    {
        $methodStart = strpos($content, "function {$methodName}(");
        if ($methodStart === false) {
            return null;
        }

        $openBrace = strpos($content, '{', $methodStart);
        if ($openBrace === false) {
            return null;
        }

        $depth = 0;
        $length = strlen($content);

        for ($i = $openBrace; $i < $length; $i++) {
            if ($content[$i] === '{') {
                $depth++;
            }

            if ($content[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($content, $openBrace + 1, $i - $openBrace - 1);
                }
            }
        }

        return null;
    }

    private function hasAaaSectionsInOrder(string $body): bool
    {
        $arrangePos = strpos($body, 'Arrange');
        $actPos = strpos($body, 'Act');
        $assertPos = strpos($body, 'Assert');

        if ($arrangePos === false || $actPos === false || $assertPos === false) {
            return false;
        }

        return $arrangePos < $actPos && $actPos < $assertPos;
    }
}
