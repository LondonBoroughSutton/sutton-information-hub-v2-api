<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\NhsConditions;

use App\Repositories\NhsConditions\NhsConditionsRepository;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class NhsConditionsRepositoryTest extends TestCase
{
    public function testFind()
    {
        $responseMock = $this->createMock(ResponseInterface::class);

        $clientMock = $this->createMock(Client::class);
        $clientMock->expects($this->once())
            ->method('get')
            ->with(
                'http://example.com/conditions/depression',
                [
                    'headers' => ['subscription-key' => 'test_key'],
                    'timeout' => 10,
                ]
            )
            ->willReturn($responseMock);

        $repository = new NhsConditionsRepository(
            $clientMock,
            'http://example.com',
            'test_key',
            10
        );

        $result = $repository->find('depression');

        $this->assertSame($responseMock, $result);
    }
}
