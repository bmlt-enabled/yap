<?php
use PHPUnit\Framework\TestCase;

final class TestIndex extends TestCase
{
    private $http;

    public function setUp()
    {
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://localhost:3100/yap/index.php']);
    }

    public function tearDown() {
        $this->http = null;
    }

    public function testIndexMethod() {
        $this->assertEquals(200, $this->http->request('GET')->getStatusCode());
    }
}
