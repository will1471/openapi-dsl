<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use PHPUnit\Framework\TestCase;
use Will1471\OpenApiDsl\CodeGen\OpenApiGenerator;
use Will1471\OpenApiDsl\Parser\Parser;

class OpenApiGeneratorTest extends TestCase
{

    public function testSimpleApi(): void
    {
        $data = <<<DATA
Account
- id: int
- name: string

AccountsResponse
- accounts: Account[]

GET /account => AccountsResponse
DATA;

        $result = (new Parser())->parse($data);

        $openApi = (new OpenApiGenerator('Title', '0.0.1', $result))->generate();
        file_put_contents(
            __DIR__ . '/../output/openapi.json',
            $json = json_encode($data = $openApi->getSerializableData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $this->assertSame('Title', $data->info->title);
        $this->assertSame('0.0.1', $data->info->version);

        $this->assertJsonStringEqualsJsonString(
            <<<JSON
{
    "openapi": "3.0.0",
    "info": {
        "title": "Title",
        "version": "0.0.1"
    },
    "paths": {
        "/account": {
            "get": {
                "responses": {
                    "200": {
                        "description": "Success",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "\$ref": "#/components/schemas/AccountsResponse"
                                }
                            }
                        }
                    }
                }
            }
        }
    },
    "components": {
        "schemas": {
            "AccountsResponse": {
                "required": [
                    "accounts"
                ],
                "type": "object",
                "properties": {
                    "accounts": {
                        "type": "array",
                        "items": {
                            "\$ref": "#/components/schemas/Account"
                        }
                    }
                }
            },
            "Account": {
                "required": [
                    "id",
                    "name"
                ],
                "type": "object",
                "properties": {
                    "id": {
                        "type": "integer"
                    },
                    "name": {
                        "type": "string"
                    }
                }
            }
        }
    }
}
JSON,
            $json
        );
    }
}
