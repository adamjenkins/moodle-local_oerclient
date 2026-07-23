<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_oerclient\local;

use PHPUnit\Framework\Attributes\CoversClass;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;

/**
 * Tests for exchange_client — specifically that a failed HTTP request never
 * leaks a live credential into the resulting exception message. Found on
 * the third MDL Shield audit pass (2026-07-18): Guzzle's default
 * RequestException::getMessage() embeds the full request URI, and
 * upload_file() used to pass the personal WS token as a query parameter —
 * a failed upload would write the teacher's live token into
 * local_oerclient_shares.errormessage, a field displayed back to the user.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(exchange_client::class)]
final class exchange_client_test extends \advanced_testcase {
    public function test_a_failed_upload_does_not_leak_the_token_in_the_exception_message(): void {
        $this->resetAfterTest();

        $secrettoken = 'super-secret-token-must-not-leak';

        // Simulate exactly what a real failure looks like: Guzzle's
        // RequestException::create() embeds the request URI (and, before the
        // fix, the URI carried ?token=<secret>) into getMessage().
        $failingrequest = new Request('POST', 'https://exchange.example/webservice/upload.php?token=' . $secrettoken);
        $mock = new MockHandler([
            RequestException::create($failingrequest, new Response(403, [], 'forbidden')),
        ]);
        $handlerstack = HandlerStack::create($mock);

        $client = new class ($handlerstack) extends exchange_client {
            /** @var \GuzzleHttp\HandlerStack */
            private \GuzzleHttp\HandlerStack $handlerstack;

            /**
             * Creates a test double pointed at a mock Guzzle handler stack.
             *
             * @param \GuzzleHttp\HandlerStack $handlerstack
             */
            public function __construct(\GuzzleHttp\HandlerStack $handlerstack) {
                parent::__construct('https://exchange.example');
                $this->handlerstack = $handlerstack;
            }

            /**
             * Returns an HTTP client wired to the mock handler stack instead of a real connection.
             *
             * @return \core\http_client
             */
            protected function client(): \core\http_client {
                return new \core\http_client(['handler' => $this->handlerstack]);
            }
        };

        $tmpfile = make_request_directory() . '/fake.mbz';
        file_put_contents($tmpfile, 'x');

        try {
            $client->upload_file($secrettoken, $tmpfile, 'fake.mbz');
            $this->fail('Expected a moodle_exception to be thrown.');
        } catch (\moodle_exception $e) {
            $this->assertStringNotContainsString($secrettoken, $e->getMessage());
            $this->assertStringNotContainsString($secrettoken, (string) $e);
        }
    }

    public function test_upload_file_sends_the_token_in_the_body_not_the_uri(): void {
        $this->resetAfterTest();

        $capturedrequest = null;
        $mock = new MockHandler([
            function (Request $request) use (&$capturedrequest) {
                $capturedrequest = $request;
                return new Response(200, [], json_encode([['itemid' => 123]]));
            },
        ]);
        $handlerstack = HandlerStack::create($mock);

        $client = new class ($handlerstack) extends exchange_client {
            /** @var \GuzzleHttp\HandlerStack */
            private \GuzzleHttp\HandlerStack $handlerstack;

            /**
             * Creates a test double pointed at a mock Guzzle handler stack.
             *
             * @param \GuzzleHttp\HandlerStack $handlerstack
             */
            public function __construct(\GuzzleHttp\HandlerStack $handlerstack) {
                parent::__construct('https://exchange.example');
                $this->handlerstack = $handlerstack;
            }

            /**
             * Returns an HTTP client wired to the mock handler stack instead of a real connection.
             *
             * @return \core\http_client
             */
            protected function client(): \core\http_client {
                return new \core\http_client(['handler' => $this->handlerstack]);
            }
        };

        $tmpfile = make_request_directory() . '/fake.mbz';
        file_put_contents($tmpfile, 'x');

        $itemid = $client->upload_file('a-real-token', $tmpfile, 'fake.mbz');

        $this->assertSame(123, $itemid);
        $this->assertNotNull($capturedrequest);
        $this->assertStringNotContainsString('a-real-token', (string) $capturedrequest->getUri());
        $this->assertStringContainsString('a-real-token', (string) $capturedrequest->getBody());
    }

    /**
     * Found in a 2026-07-19 code review of the sibling block_oerclient:
     * neither Guzzle nor core\http_client default to a request timeout
     * (Guzzle's default is 0 = wait forever, and a hung socket read doesn't
     * count against PHP's max_execution_time), so an Exchange that accepts
     * a connection but never responds would hang every caller indefinitely
     * - including block_oerclient's Dashboard panel, which makes this call
     * synchronously on every page load for every user. Asserts the fix:
     * bounded connect/total timeouts on the client every call site shares.
     */
    public function test_the_http_client_has_bounded_timeouts(): void {
        $this->resetAfterTest();

        $client = new exchange_client('https://exchange.example');
        $method = new \ReflectionMethod(exchange_client::class, 'client');
        $method->setAccessible(true);
        $httpclient = $method->invoke($client);

        $this->assertGreaterThan(0, $httpclient->getConfig('connect_timeout'));
        $this->assertGreaterThan(0, $httpclient->getConfig('timeout'));
    }
}
