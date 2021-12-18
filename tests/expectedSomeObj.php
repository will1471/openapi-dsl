<?php

namespace Will1471\CodeGen;

final class SomeObj
{
    private int $id;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @param array $array
     * @psalm-param array{id: int} $array
     * @return SomeObj
     */
    public static function fromArray(array $array) : \Will1471\CodeGen\SomeObj
    {
        return new SomeObj($array['id']);
    }

    /**
     * @param array $array
     * @return array
     * @psalm-return array{id: int}
     */
    public static function fromUntypedArray(array $array) : array
    {
        $r = [];
        if (!array_key_exists('id', $array)) {
            throw new \Exception('Property "id" is required, but missing.');
        }
        if (!is_int($array['id'])) {
            throw new \Exception('Property id should be int, but found' . get_debug_type($array['id']));
        }
        $r['id'] = $array['id'];
        return $r;
    }

    public function getId() : int
    {
        return $this->id;
    }
}
