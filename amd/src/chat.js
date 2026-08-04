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
 * Course AI Guide modal controller.
 *
 * @module     block_courseaiguide/chat
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Modal from 'core/modal';
import Templates from 'core/templates';
import {get_strings as getStrings} from 'core/str';
import Notification from 'core/notification';

/**
 * Render safe server data with DOM text nodes.
 *
 * @param {HTMLElement} root
 * @param {Object} result
 */
const renderResult = (root, result) => {
    const factsSection = root.querySelector('[data-region="facts"]');
    const factsList = root.querySelector('[data-region="fact-list"]');
    factsList.replaceChildren();
    (result.facts || []).forEach((fact) => {
        const item = document.createElement('li');
        const label = document.createElement('strong');
        label.textContent = `${fact.label}: `;
        item.append(label, document.createTextNode(fact.value));
        if (fact.url) {
            const link = document.createElement('a');
            link.href = fact.url;
            link.textContent = fact.label;
            link.className = 'ml-2';
            item.append(link);
        }
        factsList.append(item);
    });
    factsSection.hidden = !result.facts || result.facts.length === 0;

    const answerSection = root.querySelector('[data-region="answer"]');
    root.querySelector('[data-region="answer-text"]').textContent = result.answer || '';
    answerSection.hidden = result.mode === 'structured';

    const sourceSection = root.querySelector('[data-region="sources"]');
    const sourceList = root.querySelector('[data-region="source-list"]');
    sourceList.replaceChildren();
    (result.sources || []).forEach((source) => {
        if (!source.url) {
            return;
        }
        const item = document.createElement('li');
        const link = document.createElement('a');
        link.href = source.url;
        link.textContent = source.title;
        item.append(link);
        sourceList.append(item);
    });
    sourceSection.hidden = sourceList.children.length === 0;
};

/**
 * Open the accessible modal.
 *
 * @param {Number} courseId
 */
const openChat = async(courseId) => {
    try {
        const [config, strings] = await Promise.all([
            Ajax.call([{
                methodname: 'block_courseaiguide_get_chat_config',
                args: {courseid: courseId},
            }])[0],
            getStrings([
                {key: 'chatheading', component: 'block_courseaiguide'},
                {key: 'loading', component: 'block_courseaiguide'},
            ]),
        ]);
        const body = await Templates.render('block_courseaiguide/chat_modal', config);
        const modal = await Modal.create({
            title: strings[0],
            body,
            show: true,
            removeOnClose: true,
        });
        const root = modal.getRoot()[0].querySelector('[data-region="courseaiguide-chat"]');
        const form = root.querySelector('[data-region="ask-form"]');
        const status = root.querySelector('[data-region="status"]');
        const question = root.querySelector('textarea[name="question"]');
        let conversationId = '';
        let cancelled = false;

        root.querySelector('[data-action="cancel"]').addEventListener('click', () => {
            cancelled = true;
            modal.hide();
        });
        form.addEventListener('submit', async(event) => {
            event.preventDefault();
            cancelled = false;
            const askButton = root.querySelector('[data-action="ask"]');
            askButton.disabled = true;
            status.textContent = strings[1];
            try {
                const save = root.querySelector('[data-region="save-history"]');
                const result = await Ajax.call([{
                    methodname: 'block_courseaiguide_ask',
                    args: {
                        courseid: courseId,
                        question: question.value,
                        savehistory: save ? save.checked : false,
                        conversationid: conversationId,
                    },
                }])[0];
                if (!cancelled) {
                    conversationId = result.conversationid || '';
                    status.textContent = '';
                    renderResult(root, result);
                    question.focus();
                }
            } catch (error) {
                if (!cancelled) {
                    status.textContent = error.message || '';
                    Notification.exception(error);
                }
            } finally {
                askButton.disabled = false;
            }
        });
        question.focus();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Initialise the block entry point.
 *
 * @param {Number} courseId
 */
export const init = (courseId) => {
    document.querySelectorAll(`[data-region="courseaiguide-block"][data-courseid="${courseId}"]`)
        .forEach((root) => {
            const button = root.querySelector('[data-action="open-courseaiguide"]');
            if (button && !button.dataset.initialised) {
                button.dataset.initialised = '1';
                button.addEventListener('click', () => openChat(courseId));
            }
        });
};
