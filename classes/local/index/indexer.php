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
namespace block_courseaiguide\local\index;

use block_courseaiguide\local\config\course_config;

/**
 * Builds a course-scoped lexical index from whitelisted Search areas.
 */
final class indexer {
    /** @var int Maximum plain-text characters accepted from one Search document. */
    private const MAXSOURCECHARS = 200000;

    /**
     * Index one course. Caller must hold the course lock.
     *
     * @param int $courseid
     */
    public function index_course(int $courseid): void {
        global $DB;

        $config = course_config::get($courseid);
        if (!$config || empty($config->enabled)) {
            return;
        }
        $context = \context_course::instance($courseid, MUST_EXIST);
        $generation = (int) $config->indexgeneration + 1;
        $DB->set_field('block_courseaiguide_course', 'indexstatus', 'indexing', ['id' => $config->id]);
        $DB->set_field('block_courseaiguide_course', 'indexerror', null, ['id' => $config->id]);

        $enabledareas = source_registry::enabled_areas(course_config::enabled_types($config));
        $contenthashes = [];
        foreach ($enabledareas as $type => $areaid) {
            $this->index_area($courseid, $context, $generation, $type, $areaid, $contenthashes);
        }

        $oldsourceids = $DB->get_fieldset_select(
            'block_courseaiguide_source',
            'id',
            'courseid = :courseid AND generation <> :generation',
            ['courseid' => $courseid, 'generation' => $generation]
        );
        if ($oldsourceids) {
            [$insql, $params] = $DB->get_in_or_equal($oldsourceids, SQL_PARAMS_NAMED, 'oldsrc');
            $DB->delete_records_select('block_courseaiguide_chunk', "sourceid $insql", $params);
            $DB->delete_records_select('block_courseaiguide_source', "id $insql", $params);
        }

        sort($contenthashes, SORT_STRING);
        $config->indexgeneration = $generation;
        $config->indexstatus = 'ready';
        $config->contenthash = hash('sha256', implode('|', $contenthashes));
        $config->indexerror = null;
        $config->timeindexed = time();
        $config->timemodified = time();
        $DB->update_record('block_courseaiguide_course', $config);
    }

    /**
     * Index a single whitelisted Search area.
     *
     * @param int $courseid
     * @param \context_course $context
     * @param int $generation
     * @param string $type
     * @param string $areaid
     * @param array $contenthashes
     */
    private function index_area(
        int $courseid,
        \context_course $context,
        int $generation,
        string $type,
        string $areaid,
        array &$contenthashes
    ): void {
        $area = \core_search\manager::get_search_area($areaid);
        if (!$area || !$area->supports_get_document_recordset()) {
            throw new \coding_exception('Unsupported configured Search area: ' . $areaid);
        }
        $recordset = $area->get_document_recordset(0, $context);
        if (!$recordset) {
            return;
        }
        try {
            foreach ($recordset as $record) {
                $document = $area->get_document($record);
                if (!$document || !$document->is_set('courseid') || (int) $document->get('courseid') !== $courseid) {
                    continue;
                }
                $this->store_document($courseid, $generation, $type, $areaid, $area, $document, $contenthashes);
            }
        } finally {
            $recordset->close();
        }
    }

    /**
     * Store one Search document and its chunks.
     *
     * @param int $courseid
     * @param int $generation
     * @param string $type
     * @param string $areaid
     * @param \core_search\base $area
     * @param \core_search\document $document
     * @param array $contenthashes
     */
    private function store_document(
        int $courseid,
        int $generation,
        string $type,
        string $areaid,
        \core_search\base $area,
        \core_search\document $document,
        array &$contenthashes
    ): void {
        global $DB;

        $contextid = $document->is_set('contextid') ? (int) $document->get('contextid') : 0;
        $itemid = $document->is_set('itemid') ? (string) $document->get('itemid') : '';
        if (!$contextid || $itemid === '') {
            return;
        }
        $sourcecontext = \context::instance_by_id($contextid, IGNORE_MISSING);
        if (!$sourcecontext || !in_array($sourcecontext->contextlevel, [CONTEXT_COURSE, CONTEXT_MODULE], true)) {
            return;
        }
        $cmid = $sourcecontext->contextlevel === CONTEXT_MODULE ? (int) $sourcecontext->instanceid : null;
        $sectionid = null;
        $modinfo = get_fast_modinfo($courseid);
        $accessdata = ['contextid' => $contextid, 'cmid' => $cmid];
        if ($cmid) {
            try {
                $cm = $modinfo->get_cm($cmid);
                $section = $modinfo->get_section_info($cm->sectionnum);
                $sectionid = $section ? (int) $section->id : null;
                $accessdata += [
                    'visible' => (bool) $cm->visible,
                    'visibleoncoursepage' => (bool) $cm->visibleoncoursepage,
                    'availability' => (string) $cm->availability,
                    'groupmode' => (int) $cm->groupmode,
                    'groupingid' => (int) $cm->groupingid,
                ];
            } catch (\Throwable $e) {
                return;
            }
        } else if ($areaid === 'core_course-section') {
            $sectionid = (int) $itemid;
        }

        $parts = [];
        foreach (['title', 'content', 'description1', 'description2'] as $field) {
            if ($document->is_set($field)) {
                $parts[] = (string) $document->get($field);
            }
        }
        $formatted = format_text(implode("\n\n", $parts), FORMAT_HTML, [
            'context' => $sourcecontext,
            'filter' => false,
            'noclean' => false,
            'para' => false,
        ]);
        $plaintext = trim(html_to_text($formatted, 0, false));
        $plaintext = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $plaintext) ?? '';
        $plaintext = \core_text::substr($plaintext, 0, self::MAXSOURCECHARS);
        if ($plaintext === '') {
            return;
        }

        $title = $document->is_set('title') ? clean_param((string) $document->get('title'), PARAM_TEXT) : $type;
        $title = \core_text::substr($title, 0, 255);
        $url = '';
        try {
            $docurl = $area->get_doc_url($document);
            if ($docurl instanceof \moodle_url) {
                $url = $docurl->out(false);
            }
        } catch (\Throwable $e) {
            $url = '';
        }
        $contenthash = hash('sha256', $plaintext);
        $contenthashes[] = $contenthash;
        $sourcekey = hash('sha256', $areaid . ':' . $itemid);
        $now = time();
        $source = $DB->get_record('block_courseaiguide_source', [
            'courseid' => $courseid,
            'sourcekey' => $sourcekey,
        ]);
        $unchanged = $source && hash_equals((string) $source->contenthash, $contenthash);
        if (!$source) {
            $source = (object) [
                'courseid' => $courseid,
                'timecreated' => $now,
            ];
        }
        $source->contextid = $contextid;
        $source->cmid = $cmid;
        $source->sectionid = $sectionid;
        $source->searcharea = $areaid;
        $source->sourceitem = $itemid;
        $source->sourcekey = $sourcekey;
        $source->sourcetype = $type;
        $source->title = $title;
        $source->url = $url;
        $source->contenthash = $contenthash;
        $source->accesshash = hash('sha256', json_encode($accessdata));
        $source->generation = $generation;
        $source->timemodified = $now;
        if (empty($source->id)) {
            $source->id = $DB->insert_record('block_courseaiguide_source', $source);
        } else {
            $DB->update_record('block_courseaiguide_source', $source);
        }
        if ($unchanged) {
            return;
        }

        $DB->delete_records('block_courseaiguide_chunk', ['sourceid' => $source->id]);
        $chunks = (new chunker())->split($plaintext);
        foreach ($chunks as $chunkno => $content) {
            $normalised = \core_text::strtolower(preg_replace('/\s+/u', ' ', $content) ?? $content);
            $DB->insert_record('block_courseaiguide_chunk', (object) [
                'sourceid' => $source->id,
                'courseid' => $courseid,
                'chunkno' => $chunkno,
                'content' => $content,
                'searchtext' => $normalised,
                'contenthash' => hash('sha256', $content),
                'charcount' => \core_text::strlen($content),
                'wordcount' => count(preg_split('/\s+/u', trim($content), -1, PREG_SPLIT_NO_EMPTY)),
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
    }
}
