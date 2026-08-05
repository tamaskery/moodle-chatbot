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

/**
 * Course AI Assistant plugin.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide\local\chat;

use block_courseaiguide\local\access\course_guard;
use block_courseaiguide\local\diagnostic\diagnostic_service;
use block_courseaiguide\local\history\history_service;
use block_courseaiguide\local\provider\provider_exception;
use block_courseaiguide\local\provider\provider_factory;
use block_courseaiguide\local\rate\rate_limiter;
use block_courseaiguide\local\rate\site_circuit_breaker;
use block_courseaiguide\local\retrieval\database_lexical_backend;
use block_courseaiguide\local\structured\query_router;
use block_courseaiguide\local\usage\usage_service;

/**
 * End-to-end secure answer orchestration.
 */
final class orchestrator {
    /**
     * Ask one independent question.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $question
     * @param bool $savehistory
     * @param string $conversationtoken
     * @param bool $diagnosticconsent Explicit consent to incident capture for this turn.
     * @return array
     */
    public function ask(
        int $courseid,
        int $userid,
        string $question,
        bool $savehistory = false,
        string $conversationtoken = '',
        bool $diagnosticconsent = false
    ): array {
        $started = microtime(true);
        $requestid = bin2hex(random_bytes(8));
        $question = trim(\core_text::substr(clean_param($question, PARAM_TEXT), 0, 2000));
        if ($question === '') {
            throw new \invalid_parameter_exception(get_string('error:emptyquestion', 'block_courseaiguide'));
        }
        $courseconfig = (new course_guard())->require_ask($courseid, $userid);
        (new rate_limiter())->consume($courseid, $userid);

        $diagnosticchunks = [];
        $providerdiagnostic = '';
        $result = (new query_router())->answer($courseid, $userid, $question);
        if ($result === null) {
            $chunks = reference_safety::filter(
                (new database_lexical_backend())->retrieve($courseid, $userid, $question)
            );
            $diagnosticchunks = $chunks;
            if (!$chunks) {
                $result = [
                    'mode' => 'notfound',
                    'answer' => get_string('notfound', 'block_courseaiguide'),
                    'facts' => [],
                    'sources' => [],
                ];
            } else {
                try {
                    (new site_circuit_breaker())->reserve();
                    $request = (new prompt_builder())->build(
                        $question,
                        (string) $courseconfig->instructions,
                        $chunks,
                        $requestid
                    );
                    $rawresponse = (new provider_factory())->create()->complete($request);
                    $result = (new answer_validator())->validate($rawresponse, $courseid, $userid, $chunks);
                } catch (provider_exception $e) {
                    $providerdiagnostic = $e->diagnostic();
                    $answer = get_string('error:provider', 'block_courseaiguide', $requestid);
                    $context = \context_course::instance($courseid);
                    if (has_capability('block/courseaiguide:manage', $context, $userid)) {
                        $answer .= ' ' . get_string(
                            'error:provideradmin',
                            'block_courseaiguide',
                            $e->diagnostic()
                        );
                    }
                    $result = [
                        'mode' => 'error',
                        'answer' => $answer,
                        'facts' => [],
                        'sources' => [],
                    ];
                }
            }
        }

        try {
            $diagnosticreceipt = (new diagnostic_service())->capture(
                $courseconfig,
                $userid,
                $diagnosticconsent,
                $question,
                $result,
                $requestid,
                $diagnosticchunks,
                $providerdiagnostic
            );
        } catch (\Throwable $e) {
            $diagnosticreceipt = ['captured' => false, 'expiresat' => 0];
        }

        $conversationtoken = clean_param($conversationtoken, PARAM_ALPHANUMEXT);
        try {
            $storedtoken = (new history_service())->store_turn(
                $courseconfig,
                $userid,
                $savehistory,
                $conversationtoken,
                $question,
                (string) $result['answer'],
                $requestid
            );
        } catch (\Throwable $e) {
            $storedtoken = '';
        }
        $result['conversationid'] = $storedtoken;
        $result['requestid'] = $requestid;
        $result['diagnosticcaptured'] = $diagnosticreceipt['captured'];
        $result['diagnosticmessage'] = $diagnosticreceipt['captured']
            ? get_string('diagnosticcaptured', 'block_courseaiguide', userdate($diagnosticreceipt['expiresat']))
            : '';
        (new usage_service())->record(
            $courseid,
            (string) $result['mode'],
            (int) round((microtime(true) - $started) * 1000)
        );
        return $result;
    }
}
