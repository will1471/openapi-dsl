<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use PHPUnit\Framework\TestCase;
use Will1471\OpenApiDsl\CodeGen\OpenApiGenerator;
use Will1471\OpenApiDsl\Parser\Parser;

/**
 * @psalm-suppress MixedPropertyFetch
 */
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
        /** @var mixed $data */
        $data = $openApi->getSerializableData();
        $this->assertInstanceOf(\stdClass::class, $data);
        file_put_contents(
            __DIR__ . '/../output/openapi.json',
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
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

    public function testApiWithPathParamsAndMultipleMethodsPerPath(): void
    {
        $data = <<<DATA
Account
- id: int
- name: string

GET /account/{id} => Account
DELETE /account/{id}
DATA;
        $result = (new Parser())->parse($data);

        $openApi = (new OpenApiGenerator('PathParamsAndMultiMethod', '0.0.2', $result))->generate();
        /** @var mixed $data */
        $data = $openApi->getSerializableData();
        $this->assertInstanceOf(\stdClass::class, $data);
        file_put_contents(
            __DIR__ . '/../output/openapi-path-params-and-multi-method.json',
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $this->assertSame('PathParamsAndMultiMethod', $data->info->title);
        $this->assertSame('0.0.2', $data->info->version);

        $this->assertJsonStringEqualsJsonString(
            <<<JSON
{
    "openapi": "3.0.0",
    "info": {
        "title": "PathParamsAndMultiMethod",
        "version": "0.0.2"
    },
    "paths": {
        "/account/{id}": {
            "parameters": [
                {
                    "name": "id",
                    "in": "path",
                    "required": true,
                    "schema": {
                        "type": "string"
                    }
                }
            ],
            "get": {
                "responses": {
                    "200": {
                        "description": "Success",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "\$ref": "#/components/schemas/Account"
                                }
                            }
                        }
                    }
                }
            },
            "delete": {
                "responses": {
                    "200": {
                        "description": "Success"
                    }
                }
            }
        }
    },
    "components": {
        "schemas": {
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

    public function testApiWithInputType(): void
    {
        $data = <<<DATA
enum MediaType
- IMAGE
- VIDEO

Media
- type: MediaType
- url: string

CreateMessageRequest
- account_id: int
- text: string
- media: Media[]

ID
- id: int

POST /message <= CreateMessageRequest => ID
DATA;
        $result = (new Parser())->parse($data);

        $openApi = (new OpenApiGenerator('Title', '0.0.1', $result))->generate();
        /** @var mixed $data */
        $data = $openApi->getSerializableData();
        $this->assertInstanceOf(\stdClass::class, $data);
        file_put_contents(
            __DIR__ . '/../output/openapi-create-message.json',
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
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
        "/message": {
            "post": {
                "requestBody": {
                    "content": {
                        "application/json": {
                            "schema": {
                                "\$ref": "#/components/schemas/CreateMessageRequest"
                            }
                        }
                    }
                },
                "responses": {
                    "200": {
                        "description": "Success",
                        "content": {
                            "application/json": {
                                "schema": {
                                    "\$ref": "#/components/schemas/ID"
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
            "ID": {
                "required": [
                    "id"
                ],
                "type": "object",
                "properties": {
                    "id": {
                        "type": "integer"
                    }
                }
            },
            "CreateMessageRequest": {
                "required": [
                    "account_id",
                    "text",
                    "media"
                ],
                "type": "object",
                "properties": {
                    "account_id": {
                        "type": "integer"
                    },
                    "text": {
                        "type": "string"
                    },
                    "media": {
                        "type": "array",
                        "items": {
                            "\$ref": "#/components/schemas/Media"
                        }
                    }
                }
            },
            "Media": {
                "required": [
                    "type",
                    "url"
                ],
                "type": "object",
                "properties": {
                    "type": {
                        "\$ref": "#/components/schemas/MediaType"
                    },
                    "url": {
                        "type": "string"
                    }
                }
            },
            "MediaType": {
                "enum": [
                    "IMAGE",
                    "VIDEO"
                ],
                "type": "string"
            }
        }
    }
}
JSON,
            $json
        );
    }
}
