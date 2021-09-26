<?php

namespace Will1471\CodeGen;

final class SomeEnum
{
    /**
     * @psalm-var 'VALUE1'|'VALUE2'
     */
    public string $value;

    /**
     * @psalm-param 'VALUE1'|'VALUE2' $string
     */
    private function __construct(string $string)
    {
        $this->value = $string;
    }

    public function eq(\Will1471\CodeGen\SomeEnum $other) : bool
    {
        return $this->value === $other->value;
    }

    /**
     * @psalm-param 'VALUE1'|'VALUE2' $string
     */
    public static function fromString(string $string) : \Will1471\CodeGen\SomeEnum
    {
        return new self($string);
    }

    /**
     * @psalm-return 'VALUE1'|'VALUE2' $string
     */
    public static function checkString(string $string) : string
    {
        if (!in_array($string, ['VALUE1', 'VALUE2'], true)) {
            throw new \Exception('');
        }
        return $string;
    }

    public static function VALUE1() : \Will1471\CodeGen\SomeEnum
    {
        return new self('VALUE1');
    }

    public static function VALUE2() : \Will1471\CodeGen\SomeEnum
    {
        return new self('VALUE2');
    }
}
