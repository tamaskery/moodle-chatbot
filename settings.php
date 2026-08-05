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
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $dbmanager = $DB->get_manager();
    if ($dbmanager->table_exists('block_courseaiguide_site')) {
        $breakerstatus = (new \block_courseaiguide\local\rate\site_circuit_breaker())->status();
        if ($breakerstatus['open']) {
            $settings->add(new admin_setting_heading(
                'block_courseaiguide/sitecircuitbreakerwarning',
                get_string('settings:sitecircuitbreaker_open', 'block_courseaiguide'),
                get_string('settings:sitecircuitbreaker_open_desc', 'block_courseaiguide', (object) [
                    'calls' => $breakerstatus['calls'],
                    'limit' => $breakerstatus['limit'],
                    'reset' => userdate($breakerstatus['resetat']),
                ])
            ));
        }
    }
    $settings->add(new admin_setting_heading(
        'block_courseaiguide/providersettings',
        get_string('settings:providerheading', 'block_courseaiguide'),
        get_string('settings:providerheading_desc', 'block_courseaiguide')
    ));
    $settings->add(new admin_setting_configtext(
        'block_courseaiguide/endpoint',
        get_string('settings:endpoint', 'block_courseaiguide'),
        get_string('settings:endpoint_desc', 'block_courseaiguide'),
        '',
        PARAM_URL
    ));
    $settings->add(new admin_setting_configtext(
        'block_courseaiguide/model',
        get_string('settings:model', 'block_courseaiguide'),
        get_string('settings:model_desc', 'block_courseaiguide'),
        '',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configpasswordunmask(
        'block_courseaiguide/apikey',
        get_string('settings:apikey', 'block_courseaiguide'),
        get_string('settings:apikey_desc', 'block_courseaiguide'),
        ''
    ));
    $settings->add(new admin_setting_description(
        'block_courseaiguide/testconnection',
        get_string('settings:testconnection', 'block_courseaiguide'),
        html_writer::link(
            new moodle_url('/blocks/courseaiguide/test_connection.php'),
            get_string('settings:testconnectionbutton', 'block_courseaiguide'),
            ['class' => 'btn btn-secondary mb-2']
        ) . html_writer::tag('p', get_string('settings:testconnection_desc', 'block_courseaiguide'))
    ));
    $settings->add(new admin_setting_configtextarea(
        'block_courseaiguide/disclaimer',
        get_string('settings:disclaimer', 'block_courseaiguide'),
        get_string('settings:disclaimer_desc', 'block_courseaiguide'),
        get_string('defaultdisclaimer', 'block_courseaiguide'),
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtext(
        'block_courseaiguide/retentiondays',
        get_string('settings:retentiondays', 'block_courseaiguide'),
        get_string('settings:retentiondays_desc', 'block_courseaiguide'),
        0,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'block_courseaiguide/diagnosticretentionhours',
        get_string('settings:diagnosticretentionhours', 'block_courseaiguide'),
        get_string('settings:diagnosticretentionhours_desc', 'block_courseaiguide'),
        0,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_courseaiguide/statisticsenabled',
        get_string('settings:statisticsenabled', 'block_courseaiguide'),
        get_string('settings:statisticsenabled_desc', 'block_courseaiguide'),
        0
    ));
    $settings->add(new admin_setting_configtext(
        'block_courseaiguide/ratelimitshort',
        get_string('settings:ratelimitshort', 'block_courseaiguide'),
        get_string('settings:ratelimitshort_desc', 'block_courseaiguide'),
        10,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'block_courseaiguide/ratelimitday',
        get_string('settings:ratelimitday', 'block_courseaiguide'),
        get_string('settings:ratelimitday_desc', 'block_courseaiguide'),
        100,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'block_courseaiguide/siteprovidercalllimit',
        get_string('settings:siteprovidercalllimit', 'block_courseaiguide'),
        get_string('settings:siteprovidercalllimit_desc', 'block_courseaiguide'),
        1000,
        PARAM_INT
    ));
}
