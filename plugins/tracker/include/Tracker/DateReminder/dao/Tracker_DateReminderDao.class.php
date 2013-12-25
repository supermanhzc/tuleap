<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/dao/include/DataAccessObject.class.php');

class Tracker_DateReminderDao extends DataAccessObject {

    /**
     * Constructor of the class
     *
     * @return Void
     */
    public function __construct() {
        parent::__construct();
        $this->tableName = 'tracker_reminder';
    }

    /**
     * Get date reminders
     *
     * @param Integer trackerId      Id of the tracker
     * @param Boolean checkReminders Retrieve only enabled reminders (optional)
     *
     * @return DataAccessResult
     */
    public function getDateReminders($trackerId, $checkReminders = true) {
        $condition = "";
        $trackerId = $this->da->escapeInt($trackerId);
        if ($checkReminders) {
            $condition = " AND status = 1";
        }
        $sql = "SELECT *
                FROM ".$this->tableName."
                WHERE tracker_id = $trackerId
                $condition
                ORDER BY reminder_id";
        return $this->retrieve($sql);
    }

    /**
     * Get trackers having at least one active reminder
     *
     * @return DataAccessResult
     */
    public function getTrackersHavingDateReminders() {
        $sql = "SELECT DISTINCT(tracker_id)
                FROM ".$this->tableName."
                WHERE status = 1
                ORDER BY reminder_id";
        return $this->retrieve($sql);
    }

    /**
     * Insert date reminder roles
     *
     * @param Integer $reminderId       Id of the reminder
     * @param Array   $roles            Array of Roles Id role
     *
     * @return Boolean
     */
    private function insertDateReminderRoles($reminderId, $roles) {
        $values = array();
        foreach($roles as $role) {
            $role = (int)$this->da->escapeInt($role);
            $values[] = " (
                    ".$reminderId.",
                    ".$role."
                )";
        }
        $values = implode(', ', $values);
        $sql = "INSERT INTO tracker_reminder_notified_roles
                    (
                    reminder_id,
                    role_id
                    )
                    VALUES ".$values;
        return $this->update($sql);
    }

    /**
     * Add a date reminder
     *
     * @param Integer $trackerId        Id of the tracker
     * @param Integer $fieldId          Id of the date field
     * @param String  $ugroups          Id of the user groups
     * @param Array   $roles            Array of Roles Id role
     * @param Integer $notificationType 0 if before, 1 if after the value of the date field
     * @param Integer $distance         Distance from the value of the date fiels
     *
     * @return Boolean
     * @return Integer
     */
    public function addDateReminder($trackerId, $fieldId, $ugroups, $roles, $notificationType = 0, $distance = 0) {
        $trackerId        = $this->da->escapeInt($trackerId);
        $fieldId          = $this->da->escapeInt($fieldId);
        $ugroups          = $this->da->quoteSmart($ugroups);
        $notificationType = $this->da->escapeInt($notificationType);
        $distance         = $this->da->escapeInt($distance);
        $sql = "INSERT INTO ".$this->tableName."
                (
                tracker_id,
                field_id,
                ugroups,
                notification_type,
                distance
                )
                VALUES
                (
                ".$trackerId.",
                ".$fieldId.",
                ".$ugroups.",
                ".$notificationType.",
                ".$distance."
                )";
        $reminderId = $this->updateAndGetLastId($sql);
        if ($reminderId && !empty($roles)) {
            return $this->insertDateReminderRoles($reminderId, $roles);
        }
        return $reminderId;
    }

    /**
     * Update a date reminder
     *
     * @param Integer $reminderId       Id of the reminder
     * @param String  $ugroups          Id of the user groups
     * @param Array   $roles            Array of Roles Id role
     * @param Integer $notificationType 0 if before, 1 if after the value of the date field
     * @param Integer $distance         Distance from the value of the date fiels
     * @param Integer $status           0 if disabled, 1 if enabled
     *
     * @return Boolean
     */
    public function updateDateReminder($reminderId, $ugroups, $roles, $notificationType = 0, $distance = 0, $status = 1) {
        $reminderId       = $this->da->escapeInt($reminderId);
        $ugroups          = $this->da->quoteSmart($ugroups);
        $notificationType = $this->da->escapeInt($notificationType);
        $distance         = $this->da->escapeInt($distance);
        $status           = $this->da->escapeInt($status);
        $sql = "Update ".$this->tableName."
                SET
                ugroups           = ".$ugroups.",
                notification_type = ".$notificationType.",
                distance          = ".$distance.",
                status            = ".$status."
                WHERE reminder_id = ".$reminderId;
        $result = $this->update($sql);

        if ($result) {
            $sql = "DELETE FROM tracker_reminder_notified_roles
                WHERE reminder_id = $reminderId";
            $result = $this->update($sql);
            if ($result && !empty($roles)) {
                return $this->insertDateReminderRoles($reminderId, $roles);
            }
        }
        return $result;
    }

    /**
     * Retrieve a date reminder given its id
     *
     * @param Integer $reminderId Id of the reminder
     *
     * @return DataAccessResult
     */
    public function searchById($reminderId) {
        $reminderId = $this->da->escapeInt($reminderId);
        $sql = "SELECT *
                FROM $this->tableName
                WHERE reminder_id = $reminderId";
        return $this->retrieve($sql);
    }

    /**
     * Retrieve duplicated date reminders given their params
     *
     * @param Integer $trackerId        Id of the tracker
     * @param Integer $fieldId          Id of the date field
     * @param Integer $notificationType 0 if before, 1 if after the value of the date field
     * @param Integer $distance         Distance from the value of the date fiels
     * @param Integer $reminderId       Id of the reminder if it is an updated one
     *
     * @return DataAccessResult
     */
    public function findReminders($trackerId, $fieldId, $notificationType, $distance, $reminderId) {
        $trackerId        = $this->da->escapeInt($trackerId);
        $fieldId          = $this->da->escapeInt($fieldId);
        $notificationType = $this->da->escapeInt($notificationType);
        $distance         = $this->da->escapeInt($distance);
        $condition  = "";
        if ($reminderId > 0) {
            $reminderId       = $this->da->escapeInt($reminderId);
            $condition = " AND reminder_id <>  ".$reminderId ;
        }
        $sql = "SELECT *
                FROM $this->tableName
                WHERE tracker_id        = $trackerId
                  AND field_id          = $fieldId
                  AND notification_type = $notificationType
                  AND distance          = $distance
                  AND status            = 1
                  ".$condition;
        return $this->retrieve($sql);
    }

    /**
     * Delete a date reminder given its id
     * 
     * @param Array $reminderId Id of the reminder
     * 
     * @return Boolean
     */
    public function deleteReminder($reminderId) {
        $reminder = $this->da->escapeInt($reminderId);
        $sql = "DELETE FROM $this->tableName
                WHERE reminder_id = $reminder";
        if ($this->update($sql)) {
             $sql = "DELETE FROM tracker_reminder_notified_roles
                WHERE reminder_id = $reminder";
            return $this->update($sql);
        }
        return false;
    }

    /**
     * Retrieve Tracker roles given its id
     *
     * @param Integer $reminderId Id of the reminder
     *
     * @return DataAccessResult
     */
    public function getRolesByReminderId($reminderId) {
        $reminderId = $this->da->escapeInt($reminderId);
        $sql = "SELECT role_id
                FROM tracker_reminder_notified_roles
                WHERE reminder_id = $reminderId";
        return $this->retrieve($sql);
    }
}

?>