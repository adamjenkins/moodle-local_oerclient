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

defined('MOODLE_INTERNAL') || die();

/**
 * Thin HTTP client for talking to an OER Exchange site: the core WS REST
 * protocol for token-authenticated calls, plus the two plain public
 * bootstrap endpoints (register, link_consume). See DESIGN.md §1
 * "Transport: ride on core web services".
 *
 * Dev-harness note: both sites here use self-signed TLS certs and resolve to
 * a private-network IP, so TLS verification and core's curl SSRF guard
 * ('ignoresecurity', normally reserved for trusted admin-configured
 * endpoints — exactly what an Exchange URL an admin typed into settings is)
 * are both disabled below. A production deployment with real DNS/certs
 * should drop both once the Exchange is a real public host.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exchange_client {
    /** @var string */
    protected string $exchangeurl;

    /**
     * @param string $exchangeurl e.g. https://vagrant.wisecat.net
     */
    public function __construct(string $exchangeurl) {
        $this->exchangeurl = rtrim($exchangeurl, '/');
    }

    /**
     * @return \core\http_client
     */
    protected function client(): \core\http_client {
        return new \core\http_client(['verify' => false, 'ignoresecurity' => true]);
    }

    /**
     * Run an HTTP request, converting any Guzzle-level failure (network
     * error, non-2xx response, timeout) into a moodle_exception with a
     * generic message. Guzzle's default RequestException::getMessage()
     * embeds the full request URI — including any token passed as a query
     * parameter — and callers of this client (share_upload_task) persist
     * exception messages verbatim into a user-facing DB field, so a raw
     * Guzzle message here would risk writing a live credential into storage.
     *
     * @param \Closure $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function safe_request(\Closure $request): \Psr\Http\Message\ResponseInterface {
        try {
            return $request();
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new \moodle_exception('exchangeerror', 'local_oerclient', '', 'request to the Exchange failed');
        }
    }

    /**
     * Call a WS function via the core REST protocol.
     *
     * @param string $function e.g. local_oerexchange_search
     * @param array $params flat scalar params
     * @param string $token
     * @return array decoded JSON response
     * @throws \moodle_exception on a WS-level exception response
     */
    public function call(string $function, array $params, string $token): array {
        $client = $this->client();
        $response = $this->safe_request(fn() => $client->request('POST', $this->exchangeurl . '/webservice/rest/server.php', [
            'form_params' => array_merge($params, [
                'wstoken' => $token,
                'wsfunction' => $function,
                'moodlewsrestformat' => 'json',
            ]),
        ]));

        $decoded = json_decode((string) $response->getBody(), true);
        if (is_array($decoded) && isset($decoded['exception'])) {
            throw new \moodle_exception('exchangeerror', 'local_oerclient', '', $decoded['message'] ?? $decoded['exception']);
        }

        return $decoded ?? [];
    }

    /**
     * Register this site with the Exchange (public, tokenless).
     *
     * @param string $name
     * @param string $url this site's own wwwroot
     * @param string $contact
     * @return int the new site id
     */
    public function register(string $name, string $url, string $contact): int {
        $client = $this->client();
        $response = $this->safe_request(fn() => $client->request('POST', $this->exchangeurl . '/local/oerexchange/register.php', [
            'form_params' => ['name' => $name, 'url' => $url, 'contact' => $contact],
        ]));
        $decoded = json_decode((string) $response->getBody(), true);
        if (empty($decoded['siteid'])) {
            throw new \moodle_exception('exchangeerror', 'local_oerclient', '', $decoded['error'] ?? 'unknown error');
        }
        return (int) $decoded['siteid'];
    }

    /**
     * Build the Exchange's account-linking connect URL for this site.
     *
     * @param int $siteid
     * @param \moodle_url $callback where the Exchange should redirect back to
     * @return \moodle_url
     */
    public function connect_url(int $siteid, \moodle_url $callback): \moodle_url {
        return new \moodle_url($this->exchangeurl . '/local/oerexchange/connect.php', [
            'siteid' => $siteid,
            'callback' => $callback->out(false),
        ]);
    }

    /**
     * Exchange a one-time link code for the personal token it was minted for.
     *
     * @param string $code
     * @return array {token, userid}
     */
    public function consume_linkcode(string $code): array {
        $client = $this->client();
        $response = $this->safe_request(fn() => $client->request('GET', $this->exchangeurl . '/local/oerexchange/link_consume.php', [
            'query' => ['code' => $code],
        ]));
        $decoded = json_decode((string) $response->getBody(), true);
        if (empty($decoded['token'])) {
            throw new \moodle_exception('exchangeerror', 'local_oerclient', '', $decoded['error'] ?? 'unknown error');
        }
        return $decoded;
    }

    /**
     * Upload a file to the Exchange's draft area via webservice/upload.php.
     *
     * @param string $token personal WS token
     * @param string $filepath
     * @param string $filename
     * @return int the resulting draftitemid
     */
    public function upload_file(string $token, string $filepath, string $filename): int {
        // The token travels in the multipart POST body, not the query string —
        // webservice/upload.php's required_param('token', ...) accepts either,
        // and Guzzle's default exception messages embed the request URI (but
        // not the body), so keeping it out of the URI keeps it out of any
        // error message safe_request() has to fall back to.
        $client = $this->client();
        $response = $this->safe_request(fn() => $client->request('POST', $this->exchangeurl . '/webservice/upload.php', [
            'multipart' => [
                [
                    'name' => 'token',
                    'contents' => $token,
                ],
                [
                    'name' => 'file_1',
                    'contents' => fopen($filepath, 'rb'),
                    'filename' => $filename,
                ],
            ],
        ]));
        $decoded = json_decode((string) $response->getBody(), true);
        if (empty($decoded[0]['itemid'])) {
            throw new \moodle_exception('exchangeerror', 'local_oerclient', '', $decoded['error'] ?? 'upload failed');
        }
        return (int) $decoded[0]['itemid'];
    }
}
