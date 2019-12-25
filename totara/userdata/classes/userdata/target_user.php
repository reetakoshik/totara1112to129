<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\userdata;

defined('MOODLE_INTERNAL') || die();

/**
 * User record with extra information used
 * for execution of user data related actions.
 *
 * All properties are read only, use get_user_record()
 * method if you need to pass object to standard APIs
 * or do any modifications.
 *
 * @property-read string $id
 * @property-read string $auth
 * @property-read string $confirmed
 * @property-read string $policyagreed
 * @property-read string $deleted
 * @property-read string $suspended
 * @property-read string $mnethostid
 * @property-read string $username
 * @property-read string $password
 * @property-read string $idnumber
 * @property-read string $firstname
 * @property-read string $lastname
 * @property-read string $email
 * @property-read string $emailstop
 * @property-read string $icq
 * @property-read string $skype
 * @property-read string $yahoo
 * @property-read string $aim
 * @property-read string $msn
 * @property-read string $phone1
 * @property-read string $phone2
 * @property-read string $institution
 * @property-read string $department
 * @property-read string $address
 * @property-read string $city
 * @property-read string $country
 * @property-read string $lang
 * @property-read string $calendartype
 * @property-read string $theme
 * @property-read string $timezone
 * @property-read string $firstaccess
 * @property-read string $lastaccess
 * @property-read string $lastlogin
 * @property-read string $currentlogin
 * @property-read string $lastip
 * @property-read string $secret
 * @property-read string $picture
 * @property-read string $url
 * @property-read string|null $description
 * @property-read string $descriptionformat
 * @property-read string $mailformat
 * @property-read string $maildigest
 * @property-read string $maildisplay
 * @property-read string $autosubscribe
 * @property-read string $trackforums
 * @property-read string $timecreated
 * @property-read string $timemodified
 * @property-read string $trustbitmask
 * @property-read string $imagealt
 * @property-read string|null $lastnamephonetic
 * @property-read string|null $firstnamephonetic
 * @property-read string|null $middlename
 * @property-read string|null $alternatename
 * @property-read string $totarasync
 *
 * @property-read int|null $contextid the user context id, this is useful especially for deleted users because the context does not exist any more
 * @property-read int $status one of constants self::STATUS_ACTIVE, self::STATUS_SUSPENDED or self::STATUS_DELETED
 */
final class target_user extends \stdClass {
    /** Both suspended and deleted flags in user record are 0 */
    public const STATUS_ACTIVE = 0;
    /** Deleted == 1 in user record, suspended flag is ignored */
    public const STATUS_DELETED = 1;
    /** Suspended == 1 and deleted == 0 in user record */
    public const STATUS_SUSPENDED = 2;

    /**
     * @var int|null the user context id
     */
    private $usercontextid = null;

    /**
     * @var int one of constants self::STATUS_ACTIVE, self::STATUS_SUSPENDED or self::STATUS_DELETED
     */
    private $userstatus;

    /**
     * @var \stdClass reference to user record
     */
    private $user;

    /**
     * target_ser constructor.
     *
     * @param \stdClass $user record from user table
     */
    public function __construct(\stdClass $user) {
        $this->user = $user;

        if ($user->deleted) {
            $extra = \totara_userdata\local\util::get_user_extras($user->id);
            if ($extra->usercontextid) {
                $this->usercontextid = (int)$extra->usercontextid;
            } else {
                $this->usercontextid = null;
            }
            $this->userstatus = self::STATUS_DELETED;

        } else {
            $usercontext = \context_user::instance($user->id);
            $this->usercontextid = (int)$usercontext->id;
            if ($user->suspended) {
                $this->userstatus = self::STATUS_SUSPENDED;
            } else {
                $this->userstatus = self::STATUS_ACTIVE;
            }
        }
    }

    /**
     * Returns a clone of raw user record.
     *
     * This is intended for APIs that require unmodified standard user record.
     *
     * @return \stdClass
     */
    public function get_user_record() {
        return clone($this->user);
    }

    /**
     * Emulation of user record with extra properties.
     * @internal
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if ($name === 'contextid') {
            return $this->usercontextid;
        }
        if ($name === 'status') {
            return $this->userstatus;
        }
        return $this->user->{$name};
    }

    /**
     * Emulation of user record with extra properties.
     * @internal
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        if ($name === 'contextid') {
            return isset($this->usercontextid);
        }
        if ($name === 'status') {
            return isset($this->userstatus);
        }
        return isset($this->user->{$name});
    }

    /**
     * Prevent all changes.
     * @internal
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        throw new \coding_exception('target_user instance cannot be modified');
    }

    /**
     * Prevent all changes.
     * @internal
     * @param string $name
     */
    public function __unset($name) {
        throw new \coding_exception('target_user instance cannot be modified');
    }

    /**
     * Returns list of user statuses.
     * @return string[]
     */
    public static function get_user_statuses() {
        return array(
            self::STATUS_ACTIVE => get_string('activeuser', 'totara_reportbuilder'),
            self::STATUS_SUSPENDED => get_string('suspendeduser', 'totara_reportbuilder'),
            self::STATUS_DELETED => get_string('deleteduser', 'totara_reportbuilder'),
        );
    }
}