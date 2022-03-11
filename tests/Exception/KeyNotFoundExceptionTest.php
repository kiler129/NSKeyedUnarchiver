<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\Exception;

use NoFlash\NSKeyedUnarchiver\Exception\KeyNotFoundException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\Exception\KeyNotFoundException
 */
class KeyNotFoundExceptionTest extends TestCase
{
    public function testCreateForArrayListsKey()
    {
        $suts = KeyNotFoundException::createForArray('unknownKey', []);

        $this->assertStringContainsString('contain unknownKey', $suts->getMessage());
    }

    public function testCreateForArrayListsKeys()
    {
        $suts = KeyNotFoundException::createForArray('', ['k1' => '', 'k2' => null, 'k3' => 'xxx']);

        $this->assertStringContainsString('k1, k2, k3', $suts->getMessage());
    }

    public function testCreateForSingleArrayListsKey()
    {
        $suts = KeyNotFoundException::createForSingleKeyArray('unknownKey', []);

        $this->assertStringContainsString('contain only unknownKey', $suts->getMessage());
    }

    public function testCreateForSingleArrayListsKeys()
    {
        $suts = KeyNotFoundException::createForSingleKeyArray('', ['k1' => '', 'k2' => null, 'k3' => 'xxx']);

        $this->assertStringContainsString('k1, k2, k3', $suts->getMessage());
    }

    public function testCreateForObjPropListsClass()
    {
        $suts = KeyNotFoundException::createForObjProp('NSFoo', '', []);

        $this->assertStringContainsString('data for NSFoo', $suts->getMessage());
    }

    public function testCreateForObjPropListsUnexpectedKey()
    {
        $suts = KeyNotFoundException::createForObjProp('', 'unexpectedKey', []);

        $this->assertStringContainsString('contain key unexpectedKey', $suts->getMessage());
    }

    public function testCreateForObjPropListsKeys()
    {
        $suts = KeyNotFoundException::createForObjProp('', '', ['k1' => '', 'k2' => null, 'k3' => 'xxx']);

        $this->assertStringContainsString('k1, k2, k3', $suts->getMessage());
    }
}
