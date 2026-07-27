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

/**
 * Thin HTTP client for talking to an OER Exchange site: the core WS REST
 * protocol for token-authenticated calls, plus the two plain public
 * bootstrap endpoints (register, link_consume). See DESIGN.md §1
 * "Transport: ride on core web services".
 *
 * Transport security: TLS verification and core's curl security guard are
 * ON unless the admin explicitly enables the 'acceptinvalidcerts' setting
 * (default off, clearly marked development-only). Earlier revisions shipped
 * verify=false + ignoresecurity unconditionally for the self-signed dev
 * harness — which meant every site and personal token travelled over
 * unverified TLS on ANY production install, harvestable by a network MITM.
 * The dev harness now opts in via the setting instead.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exchange_client {
    /** @var string */
    protected string $exchangeurl;

    /**
     * Creates a client for a specific Exchange site.
     *
     * @param string $exchangeurl e.g. https://vagrant.wisecat.net
     */
    public function __construct(string $exchangeurl) {
        $this->exchangeurl = rtrim($exchangeurl, '/');
    }

    /**
     * Builds the underlying HTTP client used for every request.
     *
     * Bounded connect/total timeouts: neither Guzzle nor core\http_client
     * default to one (Guzzle's default is 0 = wait forever), and a hung
     * socket read doesn't count against PHP's max_execution_time. Without
     * this, a slow/half-hung Exchange stalls every caller indefinitely -
     * including block_oerclient's Dashboard panel, which makes this same
     * call synchronously on every page load for every user (found in a
     * 2026-07-19 code review of the sibling block). A timeout surfaces as
     * GuzzleHttp\Exception\ConnectException, itself a GuzzleException, so
     * safe_request()'s existing catch already handles it - no other change
     * needed to propagate this as a normal exchangeerror.
     *
     * @return \core\http_client
     */
    protected function client(): \core\http_client {
        return new \core\http_client(self::http_options());
    }

    /**
     * The transport options every request to the Exchange uses — shared
     * with import_manager::download() so there is exactly one place that
     * decides whether the insecure dev-only mode is active.
     *
     * @return array Guzzle/core\http_client options
     */
    public static function http_options(): array {
        // Default off: TLS verified, core's blocked-hosts/allowed-ports
        // curl guard active. The setting exists for dev rigs with
        // self-signed certs on private IPs and says so in its description.
        $insecure = (bool) get_config('local_oerclient', 'acceptinvalidcerts');
        return [
            'verify' => !$insecure,
            'ignoresecurity' => $insecure,
            'connect_timeout' => 3,
            'timeout' => 10,
        ];
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
            throw new \moodle_exception(
                'exchangeerror',
                'local_oerclient',
                '',
                get_string('error_requestfailed', 'local_oerclient')
            );
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

        // A bare JSON scalar (or invalid JSON) must not fatal the declared
        // array return type.
        return is_array($decoded) ? $decoded : [];
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
            throw new \moodle_exception(
                'exchangeerror',
                'local_oerclient',
                '',
                $decoded['error'] ?? get_string('error_unknown', 'local_oerclient')
            );
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
        $url = $this->exchangeurl . '/local/oerexchange/link_consume.php';
        $response = $this->safe_request(fn() => $client->request('GET', $url, [
            'query' => ['code' => $code],
        ]));
        $decoded = json_decode((string) $response->getBody(), true);
        if (empty($decoded['token'])) {
            throw new \moodle_exception(
                'exchangeerror',
                'local_oerclient',
                '',
                $decoded['error'] ?? get_string('error_unknown', 'local_oerclient')
            );
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
            throw new \moodle_exception(
                'exchangeerror',
                'local_oerclient',
                '',
                $decoded['error'] ?? get_string('error_uploadfailed', 'local_oerclient')
            );
        }
        return (int) $decoded[0]['itemid'];
    }
}
