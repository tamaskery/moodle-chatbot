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
 * Site circuit-breaker tests.
 *
 * @package    block_courseaiguide
 * @copyright  2026 Tamas Kery <tom@tomkery.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_courseaiguide;

use block_courseaiguide\local\rate\site_circuit_breaker;

/**
 * Tests the atomic site-wide provider-call ceiling.
 *
 * @covers \block_courseaiguide\local\rate\site_circuit_breaker
 */
final class site_circuit_breaker_test extends \advanced_testcase {
    /**
     * Reaching the ceiling opens the circuit and sends one admin notification.
     */
    public function test_ceiling_opens_circuit_and_notifies_admins_once(): void {
        $this->resetAfterTest();
        set_config('siteprovidercalllimit', 2, 'block_courseaiguide');
        $messagesink = $this->redirectMessages();
        $breaker = new site_circuit_breaker();

        $breaker->reserve();
        $this->assertFalse($breaker->is_open());
        $breaker->reserve();

        $status = $breaker->status();
        $this->assertTrue($status['open']);
        $this->assertSame(2, $status['calls']);
        $this->assertSame(2, $status['limit']);
        $messages = $messagesink->get_messages();
        $this->assertNotEmpty($messages);
        $firstmessage = reset($messages);
        $this->assertSame('block_courseaiguide', $firstmessage->component);
        $this->assertSame('sitecircuitbreaker', $firstmessage->name);

        try {
            $breaker->reserve();
            $this->fail('An open site circuit must reject another provider call.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:sitecircuitbreaker', $e->errorcode);
        }
        $this->assertCount(count($messages), $messagesink->get_messages());
        $messagesink->close();
    }

    /**
     * A new UTC day starts a fresh independent provider-call allowance.
     */
    public function test_new_utc_day_resets_circuit(): void {
        $this->resetAfterTest();
        set_config('siteprovidercalllimit', 1, 'block_courseaiguide');
        $messagesink = $this->redirectMessages();
        $daystart = intdiv(time(), DAYSECS) * DAYSECS;
        $breaker = new site_circuit_breaker();

        $breaker->reserve($daystart + 1);
        $this->assertTrue($breaker->is_open($daystart + 2));
        $this->assertFalse($breaker->is_open($daystart + DAYSECS));
        $breaker->reserve($daystart + DAYSECS);

        $status = $breaker->status($daystart + DAYSECS);
        $this->assertTrue($status['open']);
        $this->assertSame(1, $status['calls']);
        $messagesink->close();
    }

    /**
     * Raising the ceiling resumes calls and arms notification for the new threshold.
     */
    public function test_raised_ceiling_rearms_circuit(): void {
        $this->resetAfterTest();
        set_config('siteprovidercalllimit', 1, 'block_courseaiguide');
        $messagesink = $this->redirectMessages();
        $breaker = new site_circuit_breaker();
        $breaker->reserve();
        $firstnotificationcount = count($messagesink->get_messages());
        $this->assertGreaterThan(0, $firstnotificationcount);

        set_config('siteprovidercalllimit', 2, 'block_courseaiguide');
        $this->assertFalse($breaker->is_open());
        $breaker->reserve();

        $this->assertTrue($breaker->is_open());
        $this->assertGreaterThan($firstnotificationcount, count($messagesink->get_messages()));
        $messagesink->close();
    }
}
