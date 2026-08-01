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

namespace local_oerclient;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Regression tests for the client-side multilang-rendering bug.
 *
 * browse.php and resource_preview.php used to escape every Exchange-supplied
 * title, creator name, section name and activity name with s(), and render
 * the resource summary with format_text(..., FORMAT_PLAIN) — which s()-escapes
 * BEFORE any filter runs (lib/classes/formatting.php). So a title marked up
 * for the multilang filter appeared on the page as visible literal
 * `<span lang="en" class="multilang">…` markup instead of collapsing to the
 * viewer's language, and summaries were never filtered or auto-linked either.
 *
 * These are legacy page scripts (they require config.php directly; there is
 * no class to instantiate), so as on the Exchange side these tests pin the
 * exact expression each sink now uses against a live, test-local filter
 * configuration — a regression back to s() or FORMAT_PLAIN fails them without
 * depending on how any particular site is configured. The block's panels,
 * which ARE reachable, are covered by block_oerclient's own tests.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class multilang_rendering_test extends \advanced_testcase {
    /**
     * Enables the exact "content and headings" trio format_string() needs
     * before any filter applies to a short string: the filter active at
     * system context, $CFG->filterall, and the filter named in
     * $CFG->stringfilters. Set here rather than read from site config — a
     * unit test must not depend on any one deployment's settings, and
     * whether an admin has enabled multilang is exactly the kind of setting
     * that differs between the sites this plugin runs on.
     */
    protected function enable_multilang(): void {
        filter_set_global_state('multilang', TEXTFILTER_ON);
        set_config('filterall', 1);
        set_config('stringfilters', 'multilang');
        \filter_manager::reset_caches();
    }

    /**
     * The context these pages format in: both set the page context to system
     * before rendering.
     *
     * @return \core\context\system
     */
    protected function context(): \core\context\system {
        return \core\context\system::instance();
    }

    /**
     * The title/name sink used by browse.php's card title and creator name,
     * and by resource_preview.php's creator name: format_string($v, true,
     * ['context' => system]). A bilingual value collapses to exactly one
     * language, and a bare ampersand in it is escaped exactly once — not
     * left raw, and not double-escaped to '&amp;amp;' by an extra s().
     */
    public function test_title_sink_renders_single_language_and_escapes_ampersand_once(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $title = '<span lang="en" class="multilang">Fish & Chips</span>'
            . '<span lang="ja" class="multilang">魚とチップス</span>';

        $formatted = format_string($title, true, ['context' => $this->context()]);

        $this->assertStringContainsString('Fish &amp; Chips', $formatted);
        $this->assertStringNotContainsString('魚とチップス', $formatted);
        $this->assertStringNotContainsString('multilang', $formatted);
        $this->assertStringNotContainsString('&amp;amp;', $formatted);
        $this->assertSame(1, substr_count($formatted, '&amp;'));
    }

    /**
     * The same sink for a Japanese viewer (?lang=ja): the other span wins.
     * Guards against a "fix" that hardcodes English.
     *
     * Sets $SESSION->forcelang directly rather than calling
     * force_current_language(), which gates on translation_exists('ja') and
     * so does nothing on a site with no Japanese language pack installed.
     * current_language() reads $SESSION->forcelang with no such gate, which
     * is exactly what ?lang=ja drives at request time.
     */
    public function test_title_sink_renders_japanese_when_current_language_is_ja(): void {
        global $SESSION;

        $this->resetAfterTest();
        $this->enable_multilang();
        $SESSION->forcelang = 'ja';

        $title = '<span lang="en" class="multilang">Chemistry</span>'
            . '<span lang="ja" class="multilang">化学</span>';

        $formatted = format_string($title, true, ['context' => $this->context()]);

        $this->assertStringContainsString('化学', $formatted);
        $this->assertStringNotContainsString('Chemistry', $formatted);
    }

    /**
     * The old sink is the negative control: even with the filter trio
     * enabled, s() still freezes the markup, proving the bug was in our call
     * and not in filter configuration.
     */
    public function test_the_old_s_sink_still_freezes_multilang_markup(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $title = '<span lang="en" class="multilang">Chemistry</span>'
            . '<span lang="ja" class="multilang">化学</span>';

        $this->assertStringContainsString('&lt;span lang=&quot;en&quot;', s($title));
    }

    /**
     * resource_preview.php's cover-image alt sink. The value goes into an
     * html_writer ATTRIBUTE, and html_writer::attribute() escapes attribute
     * values with s() itself — so the filtered value must be decoded back to
     * plain text first, or its ampersand escaping is applied a second time
     * and the alt text reads "Fish &amp; Chips".
     *
     * format_string(..., ['escape' => false]) is the narrower form and is
     * not enough: 'escape' governs format_string's own ampersand escaping
     * only (lib/classes/formatting.php), while clean_text() still encodes a
     * bare '&' — and it does nothing at all for a title stored with a
     * pre-encoded entity. html_entity_decode() after filtering covers both,
     * and costs no safety: clean_text() has already run, and html_writer
     * escapes the attribute on the way out.
     */
    public function test_alt_attribute_sink_filters_and_escapes_the_ampersand_exactly_once(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $title = '<span lang="en" class="multilang">Fish & Chips</span>'
            . '<span lang="ja" class="multilang">魚とチップス</span>';

        $rendered = \html_writer::empty_tag('img', [
            'src' => 'https://exchange.example.com/cover.jpg',
            'alt' => get_string(
                'thumbnailalt',
                'local_oerclient',
                html_entity_decode(
                    format_string($title, true, ['context' => $this->context()]),
                    ENT_QUOTES,
                    'UTF-8'
                )
            ),
        ]);

        $this->assertStringContainsString('Fish &amp; Chips', $rendered);
        $this->assertStringNotContainsString('魚とチップス', $rendered);
        $this->assertStringNotContainsString('multilang', $rendered);
        $this->assertStringNotContainsString('&amp;amp;', $rendered);
        $this->assertSame(1, substr_count($rendered, '&amp;'));
    }

    /**
     * Negative control for the option above: leaving 'escape' at its default
     * in an attribute really does double-escape, so the option is doing work
     * rather than being cargo-cult.
     */
    public function test_alt_attribute_sink_double_escapes_without_the_escape_option(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $rendered = \html_writer::empty_tag('img', [
            'src' => 'https://exchange.example.com/cover.jpg',
            'alt' => format_string('Fish & Chips', true, ['context' => $this->context()]),
        ]);

        $this->assertStringContainsString('&amp;amp;', $rendered);
    }

    /**
     * resource_preview.php's summary sink: format_text(..., FORMAT_HTML,
     * ['context' => system]), with cleaning left ON. FORMAT_PLAIN (the old
     * value) s()-escapes before filtering ever runs, so multilang spans
     * could never collapse through it.
     */
    public function test_summary_sink_renders_single_language_via_format_html(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $summary = '<p><span lang="en" class="multilang">Overview text</span>'
            . '<span lang="ja" class="multilang">概要テキスト</span></p>';

        $formatted = format_text($summary, FORMAT_HTML, ['context' => $this->context()]);

        $this->assertStringContainsString('Overview text', $formatted);
        $this->assertStringNotContainsString('概要テキスト', $formatted);
        $this->assertStringNotContainsString('class="multilang"', $formatted);
    }

    /**
     * The summary arrives over a web service from a REMOTE Exchange site, so
     * it is untrusted and the purifier must run: no 'noclean' => true, ever.
     * This pins that decision — a scripted summary must not reach the page.
     */
    public function test_summary_sink_is_cleaned_because_the_remote_exchange_is_untrusted(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $summary = '<p>Harmless<script>alert(1)</script></p>';

        $formatted = format_text($summary, FORMAT_HTML, ['context' => $this->context()]);

        $this->assertStringNotContainsString('<script>', $formatted);
        $this->assertStringNotContainsString('alert(1)', $formatted);
        $this->assertStringContainsString('Harmless', $formatted);
    }

    /**
     * The old summary sink is the negative control: FORMAT_PLAIN shows the
     * raw spans even with the filter trio enabled.
     */
    public function test_format_plain_still_shows_raw_spans_even_when_the_filter_is_enabled(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $summary = '<span lang="en" class="multilang">Overview text</span>'
            . '<span lang="ja" class="multilang">概要テキスト</span>';

        $formatted = format_text($summary, FORMAT_PLAIN);

        $this->assertStringContainsString('&lt;span lang=&quot;en&quot;', $formatted);
    }

    /**
     * browse.php's catalogue-card summary teaser: filter with
     * format_text(FORMAT_HTML), THEN strip tags/decode entities with
     * content_to_text(FORMAT_HTML), THEN shorten_text(), THEN s() exactly
     * once — the order the Exchange's own index.php card summary uses.
     * Filtering must happen before stripping, or multilang never collapses;
     * the old strip_tags() + s() pair did neither and double-escaped
     * pre-encoded entities.
     */
    public function test_card_summary_teaser_filters_before_stripping_and_escapes_once(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $summary = '<span lang="en" class="multilang">Fish & Chip Shop overview</span>'
            . '<span lang="ja" class="multilang">魚とチップス店の概要</span>';

        $summaryfiltered = format_text($summary, FORMAT_HTML, ['context' => $this->context()]);
        $rendered = s(shorten_text(content_to_text($summaryfiltered, FORMAT_HTML), 140));

        $this->assertStringContainsString('Fish &amp; Chip Shop overview', $rendered);
        $this->assertStringNotContainsString('魚とチップス店の概要', $rendered);
        $this->assertStringNotContainsString('multilang', $rendered);
        $this->assertStringNotContainsString('&amp;amp;', $rendered);
        $this->assertSame(1, substr_count($rendered, '&amp;'));
    }

    /**
     * resource_preview.php's structure preview: the activity title is the
     * author's text and is filtered; the module name beside it comes from
     * the backup's <modulename> element ('quiz') — a plugin name, never
     * multilang — so it stays s()-escaped.
     */
    public function test_structure_preview_activity_title_is_filtered_modulename_is_not(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $activitytitle = '<span lang="en" class="multilang">Week 1 Quiz</span>'
            . '<span lang="ja" class="multilang">第1週クイズ</span>';

        $line = s('quiz') . ': ' . format_string($activitytitle, true, ['context' => $this->context()]);

        $this->assertSame('quiz: Week 1 Quiz', $line);
    }

    /**
     * The section-title branch: a real section name is filtered, while an
     * unnamed section (whose backup stores just the bare section number)
     * keeps the digits-only lang-string branch, which needs neither
     * escaping nor filtering.
     */
    public function test_structure_preview_section_titles(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $named = '<span lang="en" class="multilang">Introduction</span>'
            . '<span lang="ja" class="multilang">はじめに</span>';

        $this->assertSame(
            'Introduction',
            format_string($named, true, ['context' => $this->context()])
        );

        // The digits-only branch: the old code wrapped this whole lang
        // string in s(), which escaped the site's own translated text along
        // with it. With a digits-only $a there is nothing to escape, so
        // dropping the s() is a no-op — this asserts exactly that.
        $numbered = get_string('sectionnumber', 'local_oerclient', '3');
        $this->assertStringContainsString('3', $numbered);
        $this->assertSame($numbered, s($numbered));
    }

    /**
     * resource_preview.php's import target-course menu.
     *
     * html_writer::select() does NOT escape option label text — it passes it
     * straight to html_writer::tag(), escaping optgroup labels only — so the
     * raw course fullname this used to pass was both unescaped and
     * unfiltered. The first assertion is the negative control that proves
     * the sink is real; the rest pin the format_string() fix.
     */
    public function test_target_course_menu_labels_are_formatted_not_raw(): void {
        $this->resetAfterTest();
        $this->enable_multilang();

        $fullname = '<span lang="en" class="multilang">Chemistry</span>'
            . '<span lang="ja" class="multilang">化学</span>';

        // Negative control: unescaped, unfiltered, straight into the option.
        $raw = \html_writer::select([7 => '<script>alert(1)</script>'], 'targetcourseid', '', false);
        $this->assertStringContainsString('<script>alert(1)</script>', $raw);

        $course = $this->getDataGenerator()->create_course(['fullname' => $fullname]);
        $coursecontext = \core\context\course::instance($course->id);
        $options = [
            $course->id => format_string($course->fullname, true, ['context' => $coursecontext]),
        ];
        $rendered = \html_writer::select($options, 'targetcourseid', '', false);

        $this->assertStringContainsString('Chemistry', $rendered);
        $this->assertStringNotContainsString('化学', $rendered);
        $this->assertStringNotContainsString('multilang', $rendered);
    }
}
