<?php
namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Tools\Validator;

final class ValidatorTest extends TestCase
{
    public function testMinLengthAndEnum(): void
    {
        $schema = [
            'required' => ['kind', 'text'],
            'properties' => [
                'kind' => ['type' => 'string', 'enum' => ['short', 'long']],
                'text' => ['type' => 'string', 'minLength' => 3],
            ],
        ];

        $this->assertTrue(Validator::validate($schema, ['kind' => 'short', 'text' => 'hey']));
        $this->assertFalse(Validator::validate($schema, ['kind' => 'invalid', 'text' => 'hey']));
        $this->assertFalse(Validator::validate($schema, ['kind' => 'short', 'text' => 'hi']));
    }
}


