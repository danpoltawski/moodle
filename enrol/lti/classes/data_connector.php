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
 * Extends the IMS Tool provider library data connector for moodle.
 *
 * @package    enrol_lti
 * @copyright  2016 John Okely <john@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lti;

defined('MOODLE_INTERNAL') || die;

use IMSGlobal\LTI\ToolProvider;
use IMSGlobal\LTI\ToolProvider\ConsumerNonce;
use IMSGlobal\LTI\ToolProvider\Context;
use IMSGlobal\LTI\ToolProvider\DataConnector\DataConnector;
use IMSGlobal\LTI\ToolProvider\ResourceLink;
use IMSGlobal\LTI\ToolProvider\ResourceLinkShare;
use IMSGlobal\LTI\ToolProvider\ResourceLinkShareKey;
use IMSGlobal\LTI\ToolProvider\ToolConsumer;
use IMSGlobal\LTI\ToolProvider\ToolProxy;
use IMSGlobal\LTI\ToolProvider\User;
use stdClass;

/**
 * Extends the IMS Tool provider library data connector for moodle.
 *
 * @package    enrol_lti
 * @copyright  2016 John Okely <john@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_connector extends DataConnector {

    /**
     * data_connector constructor.
     */
    public function __construct() {
        parent::__construct(null, 'enrol_lti_');
    }

    /**
     * Load tool consumer object.
     *
     * @param ToolConsumer $consumer ToolConsumer object
     * @return boolean True if the tool consumer object was successfully loaded
     */
    public function loadToolConsumer($consumer) {
        global $DB;

        $table = $this->dbTableNamePrefix . DataConnector::CONSUMER_TABLE_NAME;

        $id = $consumer->getRecordId();

        if (!empty($id)) {
            $results = $DB->get_records($table, ['consumer_pk' => $id]);
        } else {
            $key256 = DataConnector::getConsumerKey($consumer->getKey());
            $results = $DB->get_records($table, ['consumer_key256' => $key256]);
        }

        foreach ($results as $row) {
            if (empty($key256) || empty($row->consumer_key) || ($consumer->getKey() === $row->consumer_key)) {
                $this->build_tool_consumer_object($row, $consumer);
                return true;
            }
        }

        return false;
    }

    /**
     * Save tool consumer object.
     *
     * @param ToolConsumer $consumer Consumer object
     * @return boolean True if the tool consumer object was successfully saved
     */
    public function saveToolConsumer($consumer) {
        global $DB;

        $table = $this->dbTableNamePrefix . DataConnector::CONSUMER_TABLE_NAME;

        $key = $consumer->getKey();
        $key256 = DataConnector::getConsumerKey($key);
        if ($key === $key256) {
            $key = null;
        }
        $protected = ($consumer->protected) ? 1 : 0;
        $enabled = ($consumer->enabled) ? 1 : 0;
        $profile = (!empty($consumer->profile)) ? json_encode($consumer->profile) : null;
        $settingsvalue = serialize($consumer->getSettings());
        $now = time();
        $data = [
            'consumer_key256' => $key256,
            'consumer_key' => $key,
            'name' => $consumer->name,
            'secret' => $consumer->secret,
            'lti_version' => $consumer->ltiVersion,
            'consumer_name' => $consumer->consumerName,
            'consumer_version' => $consumer->consumerVersion,
            'consumer_guid' => $consumer->consumerGuid,
            'profile' => $profile,
            'tool_proxy' => $consumer->toolProxy,
            'settings' => $settingsvalue,
            'protected' => $protected,
            'enabled' => $enabled,
            'enable_from' => $consumer->enableFrom,
            'enable_until' => $consumer->enableUntil,
            'last_access' => $consumer->lastAccess,
            'updated' => $now,
        ];

        $id = $consumer->getRecordId();

        if (empty($id)) {
            $data['created'] = $now;
            $sql = $this->build_insert_sql($table, array_keys($data));
        } else {
            $sql = $this->build_update_sql($table, array_keys($data), "consumer_pk = :consumer_pk");
            $data['consumer_pk'] = $id;
        }

        // Use $DB->execute(), since $DB->insert*/update*() functions require the column 'id', which LTI2 tables don't have.
        if ($DB->execute($sql, $data)) {
            if (empty($id)) {
                if ($consumerrecord = $DB->get_record($table, ['consumer_key256' => $key256])) {
                    $consumer->setRecordId($consumerrecord->consumer_pk);
                    $consumer->created = $now;
                }
            }
            $consumer->updated = $now;
            return true;
        }

        return false;
    }

    /**
     * Delete tool consumer object and related records.
     *
     * @param ToolConsumer $consumer Consumer object
     * @return boolean True if the tool consumer object was successfully deleted
     */
    public function deleteToolConsumer($consumer) {
        global $DB;

        $consumerpk = $consumer->getRecordId();
        $deletecondition = ['consumer_pk' => $consumerpk];

        // Delete any nonce values for this consumer.
        $noncetable = $this->dbTableNamePrefix . DataConnector::NONCE_TABLE_NAME;
        $DB->delete_records($noncetable, $deletecondition);

        // Delete any outstanding share keys for resource links for this consumer.
        $resourcelinksharekeytable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME;
        $resourcelinktable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_TABLE_NAME;
        $where = "resource_link_pk IN (
                      SELECT rl.resource_link_pk
                        FROM {{$resourcelinktable}} rl
                       WHERE rl.consumer_pk = :consumer_pk
                  )";
        $DB->delete_records_select($resourcelinksharekeytable, $where, $deletecondition);

        // Delete any outstanding share keys for resource links for contexts in this consumer.
        $contexttable = $this->dbTableNamePrefix . DataConnector::CONTEXT_TABLE_NAME;
        $where = "resource_link_pk IN (
                      SELECT rl.resource_link_pk
                        FROM {{$resourcelinktable}} rl
                  INNER JOIN {{$contexttable}} c
                          ON rl.context_pk = c.context_pk
                       WHERE c.consumer_pk = :consumer_pk
                )";
        $DB->delete_records_select($resourcelinksharekeytable, $where, $deletecondition);

        // Delete any users in resource links for this consumer.
        $userresulttable = $this->dbTableNamePrefix . DataConnector::USER_RESULT_TABLE_NAME;
        $where = "resource_link_pk IN (
                    SELECT rl.resource_link_pk
                      FROM {{$resourcelinktable}} rl
                     WHERE rl.consumer_pk = :consumer_pk
                )";
        $DB->delete_records_select($userresulttable, $where, $deletecondition);

        // Delete any users in resource links for contexts in this consumer.
        $where = "resource_link_pk IN (
                         SELECT rl.resource_link_pk
                           FROM {{$resourcelinktable}} rl
                     INNER JOIN {{$contexttable}} c
                             ON rl.context_pk = c.context_pk
                          WHERE c.consumer_pk = :consumer_pk
                )";
        $DB->delete_records_select($userresulttable, $where, $deletecondition);

        // Update any resource links for which this consumer is acting as a primary resource link.
        $updatecolumns = [
            'primary_resource_link_pk' => null,
            'share_approved' => null
        ];
        $where = "primary_resource_link_pk IN (
                    SELECT rl.resource_link_pk
                      FROM {{$resourcelinktable}} rl
                     WHERE rl.consumer_pk = :consumer_pk
                )";
        if ($updaterecords = $DB->get_records_select($resourcelinktable, $where, $deletecondition)) {
            foreach ($updaterecords as $record) {
                $updateparam = ['resource_link_pk' => $record->resource_link_pk];
                $sql = $this->build_update_sql($resourcelinktable, $updatecolumns, 'resource_link_pk = :resource_link_pk');
                // Use $DB->execute(), since $DB->update*() functions require the column 'id', which LTI2 tables don't have.
                $DB->execute($sql, $updateparam);
            }
        }

        // Update any resource links for contexts in which this consumer is acting as a primary resource link.
        $where = "primary_resource_link_pk IN (
                        SELECT rl.resource_link_pk
                          FROM {{$resourcelinktable}} rl
                    INNER JOIN {{$contexttable}} c
                            ON rl.context_pk = c.context_pk
                         WHERE c.consumer_pk = :consumer_pk
                )";
        if ($updaterecords = $DB->get_records_select($resourcelinktable, $where, $deletecondition)) {
            foreach ($updaterecords as $record) {
                $updateparam = ['resource_link_pk' => $record->resource_link_pk];
                $sql = $this->build_update_sql($resourcelinktable, $updatecolumns, 'resource_link_pk = :resource_link_pk');
                // Use $DB->execute(), since $DB->update*() functions require the column 'id', which LTI2 tables don't have.
                $DB->execute($sql, $updateparam);
            }
        }

        // Delete any resource links for contexts in this consumer.
        $where = "context_pk IN (
                      SELECT c.context_pk
                        FROM {{$contexttable}} c
                       WHERE c.consumer_pk = :consumer_pk
                )";
        $DB->delete_records_select($resourcelinktable, $where, $deletecondition);

        // Delete any resource links for this consumer.
        $DB->delete_records($resourcelinktable, $deletecondition);

        // Delete any contexts for this consumer.
        $DB->delete_records($contexttable, $deletecondition);

        // Delete consumer.
        $consumertable = $this->dbTableNamePrefix . DataConnector::CONSUMER_TABLE_NAME;
        $DB->delete_records($consumertable, $deletecondition);

        $consumer->initialize();

        return true;
    }

    /**
     * Load all tool consumers from the database.
     * @return array
     */
    public function getToolConsumers() {
        global $DB;
        $consumers = [];

        $consumertable = $this->dbTableNamePrefix . DataConnector::CONSUMER_TABLE_NAME;
        if ($rsconsumers = $DB->get_records($consumertable, null, 'name')) {
            foreach ($rsconsumers as $row) {
                $consumer = new ToolProvider\ToolConsumer($row->consumer_key, $this);
                $this->build_tool_consumer_object($row, $consumer);
                $consumers[] = $consumer;
            }
        }

        return $consumers;
    }

    /*
     * ToolProxy methods.
     */

    /**
     * Load the tool proxy from the database.
     *
     * @param ToolProxy $toolproxy
     * @return bool
     */
    public function loadToolProxy($toolproxy) {
        return false;
    }

    /**
     * Save the tool proxy to the database.
     *
     * @param ToolProxy $toolproxy
     * @return bool
     */
    public function saveToolProxy($toolproxy) {
        return false;
    }

    /**
     * Delete the tool proxy from the database.
     *
     * @param ToolProxy $toolproxy
     * @return bool
     */
    public function deleteToolProxy($toolproxy) {
        return false;
    }

    /*
     * Context methods.
     */

    /**
     * Load context object.
     *
     * @param Context $context Context object
     * @return boolean True if the context object was successfully loaded
     */
    public function loadContext($context) {
        global $DB;
        $table = $this->dbTableNamePrefix . DataConnector::CONTEXT_TABLE_NAME;
        if (!empty($context->getRecordId())) {
            $params = ['context_pk' => $context->getRecordId()];
        } else {
            $params = [
                'consumer_pk' => $context->getConsumer()->getRecordId(),
                'lti_context_id' => $context->ltiContextId
            ];
        }
        if ($row = $DB->get_record($table, $params)) {
            $context->setRecordId($row->context_pk);
            $context->setConsumerId($row->consumer_pk);
            $context->ltiContextId = $row->lti_context_id;
            $settings = unserialize($row->settings);
            if (!is_array($settings)) {
                $settings = array();
            }
            $context->setSettings($settings);
            $context->created = $row->created;
            $context->updated = $row->updated;
            return true;
        }

        return false;
    }

    /**
     * Save context object.
     *
     * @param Context $context Context object
     * @return boolean True if the context object was successfully saved
     */
    public function saveContext($context) {
        global $DB;
        $now = time();
        $settingsvalue = serialize($context->getSettings());
        $id = $context->getRecordId();
        $consumerpk = $context->getConsumer()->getRecordId();
        $table = $this->dbTableNamePrefix . DataConnector::CONTEXT_TABLE_NAME;
        $isinsert = empty($id);
        if ($isinsert) {
            $params = [
                'consumer_pk' => $consumerpk,
                'lti_context_id' => $context->ltiContextId,
                'settings' => $settingsvalue,
                'created' => $now,
                'updated' => $now,
            ];
            $sql = $this->build_insert_sql($table, array_keys($params));
        } else {
            $params = [
                'lti_context_id' => $context->ltiContextId,
                'settings' => $settingsvalue,
                'updated' => $now,
            ];
            $updatewhere = 'consumer_pk = :consumer_pk AND context_pk = :context_pk';
            $sql = $this->build_update_sql($table, array_keys($params), $updatewhere);
            $params['consumer_pk'] = $consumerpk;
            $params['context_pk'] = $id;
        }

        // Use $DB->execute(), since $DB->insert*/update*() functions require the column 'id', which LTI2 tables don't have.
        if ($DB->execute($sql, $params)) {
            if ($isinsert) {
                // The fields consumer_pk, lti_context_id, created and updated should be enough to identify the data we added.
                unset($params['settings']);
                if ($contextrecord = $DB->get_record($table, $params)) {
                    $context->setRecordId($contextrecord->context_pk);
                    $context->created = $now;
                }
            }
            $context->updated = $now;
            return true;
        }

        return false;
    }

    /**
     * Delete context object.
     *
     * @param Context $context Context object
     * @return boolean True if the Context object was successfully deleted
     */
    public function deleteContext($context) {
        global $DB;

        $contextid = $context->getRecordId();

        $resourcelinksharekeytable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME;
        $resourcelinktable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_TABLE_NAME;
        $userresulttable = $this->dbTableNamePrefix . DataConnector::USER_RESULT_TABLE_NAME;
        $contexttable = $this->dbTableNamePrefix . DataConnector::CONTEXT_TABLE_NAME;

        $params = ['context_pk' => $contextid];

        // Delete any outstanding share keys for resource links for this context.
        $where = "resource_link_pk IN (
                    SELECT rl.resource_link_pk
                      FROM {{$resourcelinktable}} rl
                     WHERE rl.context_pk = :context_pk
               )";
        $DB->delete_records_select($resourcelinksharekeytable, $where, $params);

        // Delete any users in resource links for this context.
        $DB->delete_records_select($userresulttable, $where, $params);

        // Update any resource links for which this consumer is acting as a primary resource link.
        $where = "primary_resource_link_pk IN (
                    SELECT rl.resource_link_pk
                      FROM {{$resourcelinktable}} rl
                     WHERE rl.context_pk = :context_pk
               )";
        if ($updaterecords = $DB->get_records_select($resourcelinktable, $where, $params)) {
            $updatecolumns = [
                'primary_resource_link_pk' => null,
                'share_approved' => null
            ];
            foreach ($updaterecords as $record) {
                $updateparam = ['resource_link_pk' => $record->resource_link_pk];
                $sql = $this->build_update_sql($resourcelinktable, $updatecolumns, 'resource_link_pk = :resource_link_pk');
                // Use $DB->execute(), since $DB->update*() functions require the column 'id', which LTI2 tables don't have.
                $DB->execute($sql, $updateparam);
            }
        }

        // Delete any resource links for this context.
        $DB->delete_records($resourcelinktable, $params);

        // Delete context.
        $DB->delete_records($contexttable, $params);

        $context->initialize();

        return true;
    }

    /*
     * ResourceLink methods
     */

    /**
     * Load resource link object.
     *
     * @param ResourceLink $resourcelink Resource_Link object
     * @return boolean True if the resource link object was successfully loaded
     */
    public function loadResourceLink($resourcelink) {
        global $DB;

        $resourcelinktable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_TABLE_NAME;
        $contexttable = $this->dbTableNamePrefix . DataConnector::CONTEXT_TABLE_NAME;

        $resourceid = $resourcelink->getRecordId();
        if (!empty($resourceid)) {
            $params = ['resource_link_pk' => $resourceid];
            $row = $DB->get_record($resourcelinktable, $params);
        } else if (!empty($resourcelink->getContext())) {
            $params = [
                'context_pk' => $resourcelink->getContext()->getRecordId(),
                'lti_resource_link_id' => $resourcelink->getId()
            ];
            $row = $DB->get_record($resourcelinktable, $params);
        } else {
            $sql = "SELECT r.*
                      FROM {{$resourcelinktable}} r
           LEFT OUTER JOIN {{$contexttable}} c
                        ON r.context_pk = c.context_pk
                     WHERE (r.consumer_pk = ? OR c.consumer_pk = ?)
                           AND lti_resource_link_id = ?";
            $params = [
                $resourcelink->getConsumer()->getRecordId(),
                $resourcelink->getConsumer()->getRecordId(),
                $resourcelink->getId()
            ];
            $row = $DB->get_record_sql($sql, $params);
        }
        if ($row) {
            $resourcelink->setRecordId($row->resource_link_pk);
            if (!is_null($row->context_pk)) {
                $resourcelink->setContextId($row->context_pk);
            } else {
                $resourcelink->setContextId(null);
            }
            if (!is_null($row->consumer_pk)) {
                $resourcelink->setConsumerId($row->consumer_pk);
            } else {
                $resourcelink->setConsumerId(null);
            }
            $resourcelink->ltiResourceLinkId = $row->lti_resource_link_id;
            $settings = unserialize($row->settings);
            if (!is_array($settings)) {
                $settings = array();
            }
            $resourcelink->setSettings($settings);
            if (!is_null($row->primary_resource_link_pk)) {
                $resourcelink->primaryResourceLinkId = $row->primary_resource_link_pk;
            } else {
                $resourcelink->primaryResourceLinkId = null;
            }
            $resourcelink->shareApproved = (is_null($row->share_approved)) ? null : ($row->share_approved == 1);
            $resourcelink->created = $row->created;
            $resourcelink->updated = $row->updated;
            return true;
        }

        return false;
    }

    /**
     * Save resource link object.
     *
     * @param ResourceLink $resourcelink Resource_Link object
     * @return boolean True if the resource link object was successfully saved
     */
    public function saveResourceLink($resourcelink) {
        global $DB;

        $table = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_TABLE_NAME;

        if (is_null($resourcelink->shareApproved)) {
            $approved = null;
        } else if ($resourcelink->shareApproved) {
            $approved = 1;
        } else {
            $approved = 0;
        }
        if (empty($resourcelink->primaryResourceLinkId)) {
            $primaryresourcelinkid = null;
        } else {
            $primaryresourcelinkid = $resourcelink->primaryResourceLinkId;
        }
        $now = time();
        $settingsvalue = serialize($resourcelink->getSettings());
        if (!empty($resourcelink->getContext())) {
            $consumerid = null;
            $contextid = $resourcelink->getContext()->getRecordId();
        } else if (!empty($resourcelink->getContextId())) {
            $consumerid = null;
            $contextid = $resourcelink->getContextId();
        } else {
            $consumerid = $resourcelink->getConsumer()->getRecordId();
            $contextid = null;
        }
        $id = $resourcelink->getRecordId();

        $data = [
            'consumer_pk' => $consumerid,
            'lti_resource_link_id' => $resourcelink->getId(),
            'settings' => $settingsvalue,
            'primary_resource_link_pk' => $primaryresourcelinkid,
            'share_approved' => $approved,
            'updated' => $now,
        ];

        $returnid = null;

        if (empty($id)) {
            $data['created'] = $now;
            $data['context_pk'] = $contextid;
            $sql = $this->build_insert_sql($table, array_keys($data));
            // Use $DB->execute(), since $DB->insert*() functions require the column 'id', which LTI2 tables don't have.
            if ($DB->execute($sql, $data)) {
                $queryconditions = $data;
                // No need for the settings column to be part of the query conditions.
                unset($queryconditions['settings']);
                $returnid = $DB->get_field($table, 'resource_link_pk', $queryconditions);
            }

        } else if ($contextid !== null) {
            $updatewhere = 'context_pk = :context_pk AND resource_link_pk = :resource_link_pk';
            $sql = $this->build_update_sql($table, array_keys($data), $updatewhere);
            $data['context_pk'] = $contextid;
            $data['resource_link_pk'] = $id;
            // Use $DB->execute(), since $DB->update*() functions require the column 'id', which LTI2 tables don't have.
            $DB->execute($sql, $data);
            $returnid = $id;

        } else {
            $updatebyconsumercolumns = $data;
            unset($updatebyconsumercolumns['consumer_pk']);
            $updatebyconsumercolumns['context_pk'];
            $updatewhere = 'consumer_pk = :consumer_pk AND resource_link_pk = :resource_link_pk';
            $sql = $this->build_update_sql($table, array_keys($updatebyconsumercolumns), $updatewhere);
            $data['context_pk'] = $contextid;
            $data['resource_link_pk'] = $id;
            // Use $DB->execute(), since $DB->update*() functions require the column 'id', which LTI2 tables don't have.
            $DB->execute($sql, $data);
            $returnid = $id;
        }

        if (!empty($returnid)) {
            if (empty($id)) {
                $resourcelink->setRecordId($returnid);
                $resourcelink->created = $now;
            }
            $resourcelink->updated = $now;
            return true;
        }

        return false;

    }

    /**
     * Delete resource link object.
     *
     * @param ResourceLink $resourcelink Resource_Link object
     * @return boolean True if the resource link object and its related records were successfully deleted.
     *                 Otherwise, a DML exception is thrown.
     */
    public function deleteResourceLink($resourcelink) {
        global $DB;

        $resourcelinksharekeytable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME;
        $userresulttable = $this->dbTableNamePrefix . DataConnector::USER_RESULT_TABLE_NAME;
        $resourcelinktable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_TABLE_NAME;

        $resourcelinkid = $resourcelink->getRecordId();
        $deleteparams = ['resource_link_pk' => $resourcelinkid];

        // Delete any outstanding share keys for resource links for this consumer.
        $DB->delete_records($resourcelinksharekeytable, $deleteparams);

        // Delete users.
        $DB->delete_records($userresulttable, $deleteparams);

        // Update any resource links for which this is the primary resource link.
        $resourcelinkdeletewhere = 'primary_resource_link_pk = :resource_link_pk';
        $sql = $this->build_update_sql($resourcelinktable, ['primary_resource_link_pk' => null], $resourcelinkdeletewhere);
        // Use $DB->execute(), since $DB->update*() functions require the column 'id', which LTI2 tables don't have.
        $DB->execute($sql, $deleteparams);

        // Delete resource link.
        $DB->delete_records($resourcelinktable, $deleteparams);

        $resourcelink->initialize();

        return true;
    }

    /**
     * Get array of user objects.
     *
     * Obtain an array of User objects for users with a result sourcedId.  The array may include users from other
     * resource links which are sharing this resource link.  It may also be optionally indexed by the user ID of a specified scope.
     *
     * @param ResourceLink $resourcelink Resource link object
     * @param boolean $localonly True if only users within the resource link are to be returned
     *                           (excluding users sharing this resource link)
     * @param int $idscope Scope value to use for user IDs
     * @return array Array of User objects
     */
    public function getUserResultSourcedIDsResourceLink($resourcelink, $localonly, $idscope) {
        global $DB;

        $users = [];
        $userresulttable = $this->dbTableNamePrefix . DataConnector::USER_RESULT_TABLE_NAME;
        $resourcelinktable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_TABLE_NAME;

        $params = ['resource_link_pk' => $resourcelink->getRecordId()];

        // Where clause for the subquery.
        $subwhere = "(resource_link_pk = :resource_link_pk AND primary_resource_link_pk IS NULL)";
        if (!$localonly) {
            $subwhere .= " OR (primary_resource_link_pk = :resource_link_pk2 AND share_approved = 1)";
            $params['resource_link_pk2'] = $resourcelink->getRecordId();
        }

        // The subquery.
        $subsql = "SELECT resource_link_pk
                     FROM {{$resourcelinktable}}
                    WHERE {$subwhere}";

        // Our main where clause.
        $where = "resource_link_pk IN ($subsql)";

        // Fields to be queried.
        $fields = 'user_pk, lti_result_sourcedid, lti_user_id, created, updated';

        // Fetch records.
        if ($records = $DB->get_records_select($userresulttable, $where, $params, '', $fields)) {
            foreach ($records as $row) {
                $user = User::fromResourceLink($resourcelink, $row->lti_user_id);
                $user->setRecordId($row->user_pk);
                $user->ltiResultSourcedId = $row->lti_result_sourcedid;
                $user->created = $row->created;
                $user->updated = $row->updated;
                if (is_null($idscope)) {
                    $users[] = $user;
                } else {
                    $users[$user->getId($idscope)] = $user;
                }
            }
        }

        return $users;
    }

    /**
     * Get array of shares defined for this resource link.
     *
     * @param ResourceLink $resourcelink Resource_Link object
     * @return array Array of ResourceLinkShare objects
     */
    public function getSharesResourceLink($resourcelink) {
        global $DB;

        $shares = [];
        $resourcelinktable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_TABLE_NAME;
        $params = ['primary_resource_link_pk' => $resourcelink->getRecordId()];
        $fields = 'resource_link_pk, share_approved, consumer_pk';
        if ($records = $DB->get_records($resourcelinktable, $params, 'consumer_pk', $fields)) {
            foreach ($records as $record) {
                $share = new ResourceLinkShare();
                $share->resourceLinkId = $record->resource_link_pk;
                $share->approved = $record->share_approved == 1;
                $shares[] = $share;
            }
        }

        return $shares;
    }

    /*
     * ConsumerNonce methods
     */

    /**
     * Load nonce object.
     *
     * @param ConsumerNonce $nonce Nonce object
     * @return boolean True if the nonce object was successfully loaded
     */
    public function loadConsumerNonce($nonce) {
        global $DB;

        $table = $this->dbTableNamePrefix . DataConnector::NONCE_TABLE_NAME;

        // Delete any expired nonce values.
        $now = time();
        $DB->delete_records_select($table, "expires <= ?", [$now]);

        // Load the nonce.
        $params = [
            'consumer_pk' => $nonce->getConsumer()->getRecordId(),
            'value' => $nonce->getValue()
        ];
        $result = $DB->get_field($table, 'value', $params);

        return !empty($result);
    }

    /**
     * Save nonce object.
     *
     * @param ConsumerNonce $nonce Nonce object
     * @return boolean True if the nonce object was successfully saved
     */
    public function saveConsumerNonce($nonce) {
        global $DB;

        $table = $this->dbTableNamePrefix . DataConnector::NONCE_TABLE_NAME;

        $data = [
            'consumer_pk' => $nonce->getConsumer()->getRecordId(),
            'value' => $nonce->getValue(),
            'expires' => $nonce->expires
        ];
        $sql = $this->build_insert_sql($table, array_keys($data));

        // Use $DB->execute(), since $DB->insert*() functions require the column 'id', which LTI2 tables don't have.
        return $DB->execute($sql, $data);
    }

    /*
     * ResourceLinkShareKey methods.
     */

    /**
     * Load resource link share key object.
     *
     * @param ResourceLinkShareKey $sharekey Resource_Link share key object
     * @return boolean True if the resource link share key object was successfully loaded
     */
    public function loadResourceLinkShareKey($sharekey) {
        global $DB;

        $resourcelinksharekeytable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME;

        // Clear expired share keys.
        $now = time();
        $where = "expires <= :expires";

        $DB->delete_records_select($resourcelinksharekeytable, $where, ['expires' => $now]);

        // Load share key.
        $fields = 'resource_link_pk, auto_approve, expires';
        if ($sharekeyrecord = $DB->get_record($resourcelinksharekeytable, ['share_key_id' => $sharekey->getId()], $fields)) {
            if ($sharekeyrecord->resource_link_pk == $sharekey->resourceLinkId) {
                $sharekey->autoApprove = $sharekeyrecord->auto_approve == 1;
                $sharekey->expires = $sharekeyrecord->expires;
                return true;
            }
        }

        return false;
    }

    /**
     * Save resource link share key object.
     *
     * @param ResourceLinkShareKey $sharekey Resource link share key object
     * @return boolean True if the resource link share key object was successfully saved
     */
    public function saveResourceLinkShareKey($sharekey) {
        global $DB;

        if ($sharekey->autoApprove) {
            $approve = 1;
        } else {
            $approve = 0;
        }

        $resourcelinksharekeytable = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME;
        $expires = $sharekey->expires;

        $params = [
            'share_key_id' => $sharekey->getId(),
            'resource_link_pk' => $sharekey->resourceLinkId,
            'auto_approve' => $approve,
            'expires' => $expires
        ];
        $sql = $this->build_insert_sql($resourcelinksharekeytable, array_keys($params));

        // Use $DB->execute(), since $DB->insert*() functions require the column 'id', which LTI2 tables don't have.
        return $DB->execute($sql, $params);
    }

    /**
     * Delete resource link share key object.
     *
     * @param ResourceLinkShareKey $sharekey Resource link share key object
     * @return boolean True if the resource link share key object was successfully deleted
     */
    public function deleteResourceLinkShareKey($sharekey) {
        global $DB;

        $table = $this->dbTableNamePrefix . DataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME;
        $DB->delete_records($table, ['share_key_id' => $sharekey->getId()]);
        $sharekey->initialize();

        return true;
    }

    /*
     * User methods
     */

    /**
     * Load user object.
     *
     * @param User $user User object
     * @return boolean True if the user object was successfully loaded
     */
    public function loadUser($user) {
        global $DB;

        $table = $this->dbTableNamePrefix . DataConnector::USER_RESULT_TABLE_NAME;

        $userid = $user->getRecordId();
        $fields = 'user_pk, resource_link_pk, lti_user_id, lti_result_sourcedid, created, updated';
        if (!empty($userid)) {
            $row = $DB->get_record($table, ['user_pk' => $userid], $fields);
        } else {
            $resourcelinkid = $user->getResourceLink()->getRecordId();
            $userid = $user->getId(ToolProvider\ToolProvider::ID_SCOPE_ID_ONLY);
            $row = $DB->get_record_select(
                $table,
                "resource_link_pk = ? AND lti_user_id = ?",
                [$resourcelinkid, $userid],
                $fields
            );
        }
        if ($row) {
            $user->setRecordId($row->user_pk);
            $user->setResourceLinkId($row->resource_link_pk);
            $user->ltiUserId = $row->lti_user_id;
            $user->ltiResultSourcedId = $row->lti_result_sourcedid;
            $user->created = $row->created;
            $user->updated = $row->updated;
            return true;
        }

        return false;
    }

    /**
     * Save user object.
     *
     * @param User $user User object
     * @return boolean True if the user object was successfully saved
     */
    public function saveUser($user) {
        global $DB;

        $now = time();
        $table = $this->dbTableNamePrefix . DataConnector::USER_RESULT_TABLE_NAME;
        $isinsert = is_null($user->created);

        $params = [
            'lti_result_sourcedid' => $user->ltiResultSourcedId,
            'updated' => $now
        ];

        if ($isinsert) {
            $params['resource_link_pk'] = $user->getResourceLink()->getRecordId();
            $params['lti_user_id'] = $user->getId(ToolProvider\ToolProvider::ID_SCOPE_ID_ONLY);
            $params['created'] = $now;
            $sql = $this->build_insert_sql($table, array_keys($params));
        } else {
            $sql = $this->build_update_sql($table, array_keys($params), 'user_pk = :user_pk');
            $params['user_pk'] = $user->getRecordId();
        }

        // Use $DB->execute(), since $DB->insert*/update*() functions require the column 'id', which LTI2 tables don't have.
        if ($DB->execute($sql, $params)) {
            if ($isinsert) {
                if ($userrecord = $DB->get_record($table, $params)) {
                    $user->setRecordId($userrecord->user_pk);
                    $user->created = $now;
                }
            }
            $user->updated = $now;
            return true;
        }

        return false;
    }

    /**
     * Delete user object.
     *
     * @param User $user User object
     * @return boolean True if the user object was successfully deleted
     */
    public function deleteUser($user) {
        global $DB;

        $usertable = $this->dbTableNamePrefix . DataConnector::USER_RESULT_TABLE_NAME;
        $DB->delete_records($usertable, ['user_pk' => $user->getRecordId()]);
        $user->initialize();

        return true;
    }

    /**
     * Builds an SQL INSERT query.
     *
     * @param string $table The table name.
     * @param array $columns The column names.
     * @return string The SQL insert query.
     */
    protected function build_insert_sql($table, $columns) {
        $params = [];
        foreach ($columns as $column) {
            $params[] = ':' . trim($column);
        }
        $columnsstring = implode(', ', $columns);
        $paramstring = implode(', ', $params);
        return "INSERT INTO {{$table}} ({$columnsstring}) VALUES ({$paramstring})";
    }

    /**
     * Builds a simple SQL UPDATE query.
     *
     * @param string $table The table name.
     * @param array $updatecolumns The array of column names to be updated.
     *                             This can be a mix of associative and numerically indexed values.
     *                             If the index is a string and the value is null, then it means that the column value
     *                             is being set to null.
     * @param string $where The conditions for the where clause.
     * @return string The SQL update query.
     */
    protected function build_update_sql($table, $updatecolumns, $where = '') {
        $setcolumns = [];
        foreach ($updatecolumns as $index => $column) {
            if (is_string($index) && $column === null) {
                $setcolumns[] = $index . ' = NULL';
            } else {
                $setcolumns[] = $column . ' = :' . trim($column);
            }
        }
        $setcolumnsstring = implode(', ', $setcolumns);

        $updatesql = "UPDATE {{$table}} SET {$setcolumnsstring}";
        if (!empty(trim($where))) {
            $updatesql .= " WHERE ({$where})";
        }

        return $updatesql;
    }

    /**
     * Builds a ToolConsumer object from a record object from the DB.
     *
     * @param stdClass $record The DB record object.
     * @param ToolConsumer $consumer
     */
    protected function build_tool_consumer_object($record, ToolConsumer $consumer) {
        $consumer->setRecordId($record->consumer_pk);
        $consumer->name = $record->name;
        $key = empty($record->consumer_key) ? $record->consumer_key256 : $record->consumer_key;
        $consumer->setkey($key);
        $consumer->secret = $record->secret;
        $consumer->ltiVersion = $record->lti_version;
        $consumer->consumerName = $record->consumer_name;
        $consumer->consumerVersion = $record->consumer_version;
        $consumer->consumerGuid = $record->consumer_guid;
        $consumer->profile = json_decode($record->profile);
        $consumer->toolProxy = $record->tool_proxy;
        $settings = unserialize($record->settings);
        if (!is_array($settings)) {
            $settings = array();
        }
        $consumer->setSettings($settings);
        $consumer->protected = $record->protected == 1;
        $consumer->enabled = $record->enabled == 1;
        $consumer->enableFrom = null;
        if (!is_null($record->enable_from)) {
            $consumer->enableFrom = $record->enable_from;
        }
        $consumer->enableUntil = null;
        if (!is_null($record->enable_until)) {
            $consumer->enableUntil = $record->enable_until;
        }
        $consumer->lastAccess = null;
        if (!is_null($record->last_access)) {
            $consumer->lastAccess = $record->last_access;
        }
        $consumer->created = $record->created;
        $consumer->updated = $record->updated;
    }
}
