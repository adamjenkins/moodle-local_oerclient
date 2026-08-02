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

/**
 * Live progress for a queued share.
 *
 * Sharing from this site is a three-stage background job — share_upload_task
 * builds a sanitized backup, uploads it to the Exchange, and records the
 * result — and the status page used to render whichever stage happened to be
 * current when the page was requested, with nothing to say it would ever
 * change. A teacher who shared a course had to guess when to press reload.
 *
 * There is no byte-level progress to report here and this deliberately does
 * not invent one: the file never leaves the browser (the backup is made and
 * uploaded server-side), so the honest unit of progress is the stage, and the
 * bar moves in four steps.
 *
 * @module     local_oerclient/share_status
 * @copyright  2026 Adam Jenkins <adam@wisecat.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import {get_string as getString} from 'core/str';

/** @type {number} milliseconds between polls. */
const POLL_INTERVAL = 3000;

/** @type {number} milliseconds after which polling gives up. */
const POLL_TIMEOUT = 10 * 60 * 1000;

/** @type {number} milliseconds the outcome is shown before the page reloads. */
const RELOAD_DELAY = 1200;

/** @type {object} how far through the job each stage is, as a percentage. */
const STAGE_PERCENT = {
    pending: 10,
    backingup: 45,
    uploading: 80,
    published: 100,
    failed: 100,
};

/**
 * Draw a stage.
 *
 * @param {HTMLElement} bar
 * @param {HTMLElement} label
 * @param {string} status
 * @param {string} message
 */
const render = async(bar, label, status, message) => {
    const percent = STAGE_PERCENT[status] ?? 10;
    bar.style.width = percent + '%';
    bar.setAttribute('aria-valuenow', String(percent));
    if (status === 'failed') {
        bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
        bar.classList.add('bg-danger');
    } else if (status === 'published') {
        bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
        bar.classList.add('bg-success');
    }
    label.textContent = message;
};

/**
 * Ask the server once.
 *
 * @param {number} shareid
 * @returns {Promise<object>}
 */
const poll = (shareid) => fetchMany([{
    methodname: 'local_oerclient_get_share_state',
    args: {shareid: shareid},
}])[0];

/**
 * Start watching a share.
 *
 * @param {number} shareid
 */
export const init = (shareid) => {
    const region = document.querySelector('[data-region="oerclient-share-progress"]');
    if (!region) {
        return;
    }
    const bar = region.querySelector('.progress-bar');
    const label = region.querySelector('[data-region="oerclient-share-stage"]');
    if (!bar || !label) {
        return;
    }

    const startedAt = Date.now();
    let lastStatus = null;

    const tick = async() => {
        let state;
        try {
            state = await poll(shareid);
        } catch (e) {
            // One failed poll is not news — try again on the next tick.
            scheduleNext();
            return;
        }

        if (state.status !== lastStatus) {
            lastStatus = state.status;
            const stagename = await getString('sharestatus_' + state.status, 'local_oerclient');
            await render(bar, label, state.status, await getString('sharestatuslabel', 'local_oerclient', stagename));
        }

        if (!state.settled) {
            scheduleNext();
            return;
        }

        // Reload for the outcome: published reveals the Exchange link, the
        // live Exchange-side detail and the "Update the shared copy" button,
        // and failed reveals the error notification — all of them server-
        // rendered, none of them worth duplicating here.
        window.setTimeout(() => window.location.reload(), RELOAD_DELAY);
    };

    const scheduleNext = () => {
        if (Date.now() - startedAt > POLL_TIMEOUT) {
            getString('sharestillrunning', 'local_oerclient')
                .then((message) => {
                    label.textContent = message;
                    return;
                })
                .catch(() => {
                    // The server-rendered stage text stays, and remains true.
                    return;
                });
            return;
        }
        window.setTimeout(tick, POLL_INTERVAL);
    };

    scheduleNext();
};
