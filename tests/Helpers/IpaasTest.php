<?php

namespace Ipaas\Gapp\Tests\Helpers;

use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Ipaas\Gapp\Logger\Client;
use Ipaas\Gapp\Tests\TestCase;

class IpaasTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();

        app()->singleton('logger-context', function () {
            return new Client();
        });
    }

    /**
     * @test
     * @throws \Exception
     */
    public function itTestsILogHelper()
    {
        $dateFrom = Carbon::now();
        $dateTo = Carbon::now()->addMinutes(10);
        $uuid = Str::uuid();
        $case = [
            'client_id' => 12,
            'client_key' => 10,
            'request_id' => 8,
            'type' => 'setType',
            'date_from' => $dateFrom->format('c'),
            'date_to' => $dateTo->format('c'),
            'uuid' => $uuid,
        ];

        $this->assertTrue(ilog() instanceof Client);

        $this->assertEquals(ilog()->getClientId(), 'Unknown');
        ilog(['client_id' => $case['client_id']]);
        $this->assertEquals(ilog()->client_id, $case['client_id']);

        $this->assertEquals(ilog()->getClientKey(), 'Unknown');
        ilog()->setClientKey($case['client_key']);
        $this->assertEquals(ilog()->getClientKey(), $case['client_key']);

        $this->assertEquals(ilog()->getRequestId(), 'Unknown');
        ilog()->setRequestId($case['request_id']);
        $this->assertEquals(ilog()->getRequestId(), $case['request_id']);

        ilog()->setType($case['type']);
        $this->assertEquals(ilog()->type, $case['type']);

        ilog()->setDateFrom();
        $this->assertNotNull(ilog()->date_from);
        ilog()->setDateFrom($case['date_from']);
        $this->assertEquals(ilog()->date_from, $case['date_from']);

        ilog()->setDateTo();
        $this->assertNotNull(ilog()->date_to);
        ilog()->setDateTo($case['date_to']);
        $this->assertEquals(ilog()->date_to, $case['date_to']);

        ilog()->setUuid();
        $this->assertNotNull(ilog()->uuid);
        ilog()->setUuid($case['uuid']);
        $this->assertEquals(ilog()->uuid, $case['uuid']);

        $this->assertEquals($case, ilog()->toArray());
    }

    /**
     * @test
     */
    public function itTestsIResponseHelper()
    {
        $iResponse = new \Ipaas\Gapp\Response();
        $iResponse->setHeaders(['Testing-Header' => true]);
        $iResponse->setMeta(['Testing-Meta' => true]);
        /** @var Response $response */
        $response = $iResponse->sendResponse('Testing Response');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseContent = $response->getOriginalContent();
        //dd($response->headers);
        $this->assertTrue($response->headers->get('testing-header') == 1);
        $this->assertTrue($responseContent['meta']['Testing-Meta']);
        $this->assertEquals('Testing Response', $responseContent['data']);
        $this->assertEquals('Unknown', $responseContent['meta']['request_id']);
        $this->assertEquals('http://localhost', $responseContent['meta']['self']);

        ilog()->setRequestId(10);
        $iResponse->setHeaders(['Testing-Header' => false]);
        $iResponse->setMeta(['Testing-Meta' => false]);
        $response = $iResponse->sendResponse('Testing a New Response');
        $responseContent = $response->getOriginalContent();

        $this->assertFalse($response->headers->get('testing-header') == 1);
        $this->assertFalse($responseContent['meta']['Testing-Meta']);
        $this->assertEquals('Testing a New Response', $responseContent['data']);
        $this->assertEquals(10, $responseContent['meta']['request_id']);
    }

    /**
     * @test
     */
    public function itTestsResponseErrors()
    {
        $iResponse = new \Ipaas\Gapp\Response();
        $response = $iResponse->sendError('Sending error', Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $response = $iResponse->sendError('Unprocessed Entity', Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertEquals('Unprocessed Entity', $this->getResponseResult($response)->messages);
        $this->assertNotNull($this->getResponseResult($response)->meta->code);

        $response = $iResponse->sendError('Unauthorized', Response::HTTP_UNAUTHORIZED);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $this->getResponseResult($response)->messages);
        $this->assertNotNull($this->getResponseResult($response)->meta->code);

        $response = $iResponse->sendError('Bad Request', Response::HTTP_BAD_REQUEST);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Bad Request', $this->getResponseResult($response)->messages);
        $this->assertNotNull($this->getResponseResult($response)->meta->code);

        $response = $iResponse->sendError('Too Many Requests', Response::HTTP_TOO_MANY_REQUESTS);
        $this->assertEquals(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertEquals('Too Many Requests', $this->getResponseResult($response)->messages);
        $this->assertNotNull($this->getResponseResult($response)->meta->code);

        $response = $iResponse->sendError('Not Found', Response::HTTP_NOT_FOUND);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('Not Found', $this->getResponseResult($response)->messages);
        $this->assertNotNull($this->getResponseResult($response)->meta->code);

        $response = $iResponse->sendError('Method not implemented', Response::HTTP_NOT_IMPLEMENTED);
        $this->assertEquals(Response::HTTP_NOT_IMPLEMENTED, $response->getStatusCode());
        $this->assertEquals('Method not implemented', $this->getResponseResult($response)->messages);
        $this->assertNotNull($this->getResponseResult($response)->meta->code);

        $response = $iResponse->sendError('Internal Server Error', Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('Internal Server Error', $this->getResponseResult($response)->messages);
        $this->assertNotNull($this->getResponseResult($response)->meta->code);
    }
}
