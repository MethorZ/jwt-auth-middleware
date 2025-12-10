<?php

declare(strict_types=1);

namespace MethorZ\JwtAuthMiddleware\Tests\Unit\Service;

use Laminas\Diactoros\ServerRequest;
use MethorZ\JwtAuthMiddleware\Service\TokenExtractor;
use PHPUnit\Framework\TestCase;

final class TokenExtractorTest extends TestCase
{
    public function testExtractReturnsTokenFromBearerHeader(): void
    {
        $extractor = new TokenExtractor();
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Bearer my-token-here'],
        );

        $token = $extractor->extract($request);

        $this->assertSame('my-token-here', $token);
    }

    public function testExtractReturnsNullWhenHeaderMissing(): void
    {
        $extractor = new TokenExtractor();
        $request = new ServerRequest();

        $token = $extractor->extract($request);

        $this->assertNull($token);
    }

    public function testExtractReturnsNullWhenPrefixMismatch(): void
    {
        $extractor = new TokenExtractor();
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Basic credentials-here'],
        );

        $token = $extractor->extract($request);

        $this->assertNull($token);
    }

    public function testExtractHandlesCustomHeader(): void
    {
        $extractor = new TokenExtractor(headerName: 'X-Auth-Token');
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['X-Auth-Token' => 'Bearer custom-token'],
        );

        $token = $extractor->extract($request);

        $this->assertSame('custom-token', $token);
    }

    public function testExtractHandlesCustomPrefix(): void
    {
        $extractor = new TokenExtractor(headerPrefix: 'JWT');
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'JWT my-token'],
        );

        $token = $extractor->extract($request);

        $this->assertSame('my-token', $token);
    }

    public function testExtractHandlesNoPrefixConfiguration(): void
    {
        $extractor = new TokenExtractor(headerPrefix: '');
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'raw-token-without-prefix'],
        );

        $token = $extractor->extract($request);

        $this->assertSame('raw-token-without-prefix', $token);
    }

    public function testExtractTrimsWhitespace(): void
    {
        $extractor = new TokenExtractor();
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Bearer   token-with-spaces   '],
        );

        $token = $extractor->extract($request);

        $this->assertSame('token-with-spaces', $token);
    }

    public function testExtractReturnsNullForEmptyToken(): void
    {
        $extractor = new TokenExtractor();
        $request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            ['Authorization' => 'Bearer '],
        );

        $token = $extractor->extract($request);

        $this->assertNull($token);
    }
}
