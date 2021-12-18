<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use PHPUnit\Framework\TestCase;
use Will1471\CodeGen\SomeEnum;
use Will1471\CodeGen\SomeObj;
use Will1471\OpenApiDsl\CodeGen\EnumGenerator;
use Will1471\OpenApiDsl\CodeGen\JsonSchemaGenerator;
use Will1471\OpenApiDsl\CodeGen\ObjGenerator;
use Will1471\OpenApiDsl\DSL\Enum;
use Will1471\OpenApiDsl\DSL\Obj;
use Will1471\OpenApiDsl\Parser\Parser;
use Will1471\OpenApiDsl\Parser\ParseResult;

use function Fp\Callable\compose;
use function Fp\Callable\partialRight;
use function Pipeline\zip;

class ModelCodeGenTest extends TestCase
{
    private function parse(string $content): ParseResult
    {
        $parser = new Parser();
        return $parser->parse($content);
    }

    public function testSimpleModel(): void
    {
        $data = <<<DATA
RequiredInt
- id : int
RequiredNullableInt
- id : ?int
OptionalInt
- id: int
- opt?: int
OptionalNullableInt
- id: int
- opt?: ?int
RequiredIntArr
- id : int[]
RequiredNullableIntArr
- id : ?int[]
OptionalIntArr
- id: int
- opt?: int[]
OptionalNullableIntArr
- id: int
- opt?: ?int[]

RequiredStr
- id : string
RequiredNullableStr
- id : ?string
OptionalStr
- id: string
- opt?: string
OptionalNullableString
- id: string
- opt?: ?string
RequiredStrArr
- id : string[]
RequiredNullableStrArr
- id : ?string[]
OptionalStrArr
- id: string
- opt?: string[]
OptionalNullableStringArr
- id: string
- opt?: ?string[]

enum SomeEnum
- VALUE1
- VALUE2

RequiredEnum
- id : SomeEnum
RequiredNullableEnum
- id : ?SomeEnum
OptionalEnum
- id: string
- opt?: SomeEnum
OptionalNullableEnum
- id: string
- opt?: ?SomeEnum
RequiredEnumArr
- id : SomeEnum[]
RequiredNullableEnumArr
- id : ?SomeEnum[]
OptionalEnumArr
- id: string
- opt?: SomeEnum[]
OptionalNullableEnumArr
- id: string
- opt?: ?SomeEnum[]

SomeObj
- id: int

RequiredObj
- id: int
- obj: SomeObj
RequiredNullableObj
- id : ?SomeObj
OptionalObj
- id: string
- opt?: SomeObj
OptionalNullableObj
- id: string
- opt?: ?SomeObj
RequiredObjArr
- id : SomeObj[]
RequiredNullableObjArr
- id : ?SomeObj[]
OptionalObjArr
- id: string
- opt?: SomeObj[]
OptionalNullableObjArr
- id: string
- opt?: ?SomeObj[]


Foo
- bar: Bar
Bar
- fish: Fish
Fish
- beans: Beans
Beans
- id: int
DATA;

        $parseResult = $this->parse($data);
        $namespace = 'Will1471\\CodeGen';

        $getName = fn(Enum|Obj $t): string => $t->name;
        $filename = fn(string $name, string $ext): string => __DIR__ . "/../output/{$name}.{$ext}";
        $phpPath = compose($getName, partialRight($filename, 'php'));
        $jsonPath = compose($getName, partialRight($filename, 'json'));

        $buildEnum = fn(Enum $enum): string => "<?php\n\n" . (new EnumGenerator($enum, $namespace))->generate();
        $buildObj = fn(Obj $obj): string => "<?php\n\n" . (new ObjGenerator($obj, $parseResult, $namespace))->generate();
        $jsonOpt = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        $buildJson = fn(Obj $obj): string => json_encode((new JsonSchemaGenerator($parseResult))->build($obj), $jsonOpt);

        $writeFile = /** @param array{string,string} $pair */ fn(array $pair): int|false => file_put_contents($pair[0], $pair[1]);

        $enums = $parseResult->enums->values();
        $objs = $parseResult->objs->values();

        zip($enums->map($phpPath)->toArray(), $enums->map($buildEnum)->toArray())->map($writeFile)->toArray();
        zip($objs->map($phpPath)->toArray(), $objs->map($buildObj)->toArray())->map($writeFile)->toArray();
        zip($objs->map($jsonPath)->toArray(), $objs->map($buildJson)->toArray())->map($writeFile)->toArray();

        $this->assertTrue(true);
    }

    /**
     * @depends testSimpleModel
     */
    public function testSomeObj(): void
    {
        $expected = file_get_contents(__DIR__ . '/expectedSomeObj.php');
        $this->assertSame($expected, file_get_contents(__DIR__ . '/../output/SomeObj.php'));
        require_once __DIR__ . '/../output/SomeObj.php';
        $typedArray = SomeObj::fromUntypedArray(['id' => 1]);
        $obj = SomeObj::fromArray($typedArray);
        $this->assertSame(1, $obj->getId());
    }

    /**
     * @depends testSimpleModel
     */
    public function testSomeEnum(): void
    {
        $expected = file_get_contents(__DIR__ . '/expectedSomeEnum.php');
        $this->assertSame($expected, file_get_contents(__DIR__ . '/../output/SomeEnum.php'));
        require_once __DIR__ . '/../output/SomeEnum.php';

        $string = SomeEnum::checkString('VALUE1');
        $this->assertSame('VALUE1', $string);

        $this->assertTrue(SomeEnum::fromString('VALUE1')->eq(SomeEnum::VALUE1()));
        $this->assertFalse(SomeEnum::fromString('VALUE1')->eq(SomeEnum::VALUE2()));
    }
}
