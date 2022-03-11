<?php
declare(strict_types=1);

namespace NoFlash\NSKeyedUnarchiver\Tests\DTO\Native;

use NoFlash\NSKeyedUnarchiver\DTO\Native\NSURL;
use NoFlash\NSKeyedUnarchiver\DTO\Native\NSUUID;
use NoFlash\NSKeyedUnarchiver\Exception\MalformedArchiveException;
use NoFlash\NSKeyedUnarchiver\Tests\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\Native\NSUUID
 * @covers \NoFlash\NSKeyedUnarchiver\DTO\FlatNSUnserializerTrait
 */
class NSUUIDTest extends TestCase
{
    use NativeClassRepresentationTrait;

    protected const EXPECTED_NATIVE_CLASS = 'NSUUID';
    protected NSUUID $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new NSUUID();
    }

    public function testUnserializesDehydratedNSUUID()
    {
        $uuid = Uuid::v4();
        $uuidBytes = $uuid->toBinary();

        $this->subjectUnderTest->__unserialize(['NS.uuidbytes' => $uuidBytes]);

        $this->assertSame($uuidBytes, $this->subjectUnderTest->uuidbytes);
    }

    public function testThrowsArchiveExceptionOnIncompatibleBytesType()
    {
        $this->expectException(MalformedArchiveException::class);
        $this->subjectUnderTest->__unserialize(['NS.uuidbytes' => 1234]);
    }
}
