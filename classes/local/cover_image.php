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
 * Cover-image thumbnails for Exchange resources, in the two shapes the
 * client side draws them.
 *
 * Unlike the Exchange's own local_oerexchange\local\cover_image, this class
 * does no file lookups at all — the client has no local storage for these
 * images; it only ever receives a cover-image URL string over the wire from
 * the Exchange's web services. Callers pass that string here after cleaning
 * it with clean_param(..., PARAM_URL), the same distrust-every-Exchange-URL
 * rule browse.php already applies to creatorprofileurl. Both shapes match
 * the Exchange's rendering (same dimensions, same Bootstrap utilities), so
 * the catalogue looks consistent across the platform. A resource whose
 * author added no cover draws this plugin's own copy of the platform's
 * default thumbnail (pix/defaultthumbnail.jpg) — served locally, never
 * fetched from the Exchange.
 *
 * @package    local_oerclient
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cover_image {
    /** @var int Height in px of the banner thumbnail on a browse card. */
    const CARD_HEIGHT = 160;

    /** @var int Side in px of the square thumbnail in a block's list row. */
    const LIST_SIZE = 56;

    /**
     * The full-width banner thumbnail at the top of a browse card.
     *
     * Always returns markup, even with no image: a grid where only some cards
     * carry a picture lands each row's text at a different height, which
     * reads as a broken page rather than as "this one has no cover". A
     * resource with no custom cover draws the platform's default thumbnail
     * at the same size.
     *
     * @param string|null $url already cleaned with clean_param(..., PARAM_URL), or null for none
     * @return string HTML
     */
    public static function card(?string $url): string {
        global $OUTPUT;

        $isdefault = $url === null || $url === '';
        $src = $isdefault ? $OUTPUT->image_url('defaultthumbnail', 'local_oerclient')->out(false) : $url;

        return \html_writer::empty_tag('img', [
            'src' => $src,
            'class' => 'oerclient-thumb card-img-top' . ($isdefault ? ' oerclient-thumb-default' : ''),
            'style' => 'height:' . self::CARD_HEIGHT . 'px;object-fit:cover;',
            'loading' => 'lazy',
            // Deliberately empty: every call site puts the resource's title
            // immediately next to this image as a link, so alt text here
            // would make a screen reader announce the same resource twice.
            'alt' => '',
        ]);
    }

    /**
     * The small square thumbnail beside a resource in a block's list.
     *
     * A resource with no custom cover draws the platform's default thumbnail
     * at the same size, for the same row-alignment reason as card().
     *
     * @param string|null $url already cleaned with clean_param(..., PARAM_URL), or null for none
     * @return string HTML
     */
    public static function listitem(?string $url): string {
        global $OUTPUT;

        $isdefault = $url === null || $url === '';
        $src = $isdefault ? $OUTPUT->image_url('defaultthumbnail', 'local_oerclient')->out(false) : $url;

        return \html_writer::empty_tag('img', [
            'src' => $src,
            'class' => 'oerclient-thumb rounded flex-shrink-0' . ($isdefault ? ' oerclient-thumb-default' : ''),
            'style' => 'width:' . self::LIST_SIZE . 'px;height:' . self::LIST_SIZE . 'px;object-fit:cover;',
            'loading' => 'lazy',
            // Empty for the same reason as card(): the title is right there.
            'alt' => '',
        ]);
    }
}
