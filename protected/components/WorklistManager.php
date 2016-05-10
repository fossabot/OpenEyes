<?php

/**
 * OpenEyes
 *
 * (C) OpenEyes Foundation, 2016
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2016, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

/**
 * This component class is intended to encaspulate the logic of interacting with the Worklists
 *
 * Class WorklistManager
 */
class WorklistManager extends CComponent
{
    public static $AUDIT_TARGET_MANUAL = "Manual Worklist";

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * Abstraction for getting model instance of class
     *
     * @param $class
     * @return mixed
     */
    protected function getModelForClass($class)
    {
        return $class::model();
    }

    /**
     * Abstraction for getting instance of class
     *
     * @param $class
     * @return mixed
     */
    protected function getInstanceForClass($class)
    {
        return new $class();
    }

    /**
     * Wrapper for starting a transaction
     *
     * @return CDbTransaction|null
     */
    protected function startTransaction()
    {
        return Yii::app()->db->getCurrentTransaction() === null
            ? Yii::app()->db->beginTransaction()
            : null;
    }

    /**
     * Wrapper for partial rendering to encapsulate the call out to the static app for retrieving the controller object.
     *
     * @param $view
     * @param array $parameters
     * @return mixed
     */
    protected function renderPartial($view, $parameters = array())
    {
        return Yii::app()->controller->renderPartial($view, $parameters,true);
    }

    /**
     * Wrapper for retrieving current active User
     *
     * @return mixed
     */
    protected function getCurrentUser()
    {
        return Yii::app()->user;
    }

    /**
     * Audit Wrapper
     *
     * @param $target
     * @param $action
     * @param null $data
     * @param null $log_message
     * @param array $properties
     * @throws Exception
     */
    protected function audit($target, $action, $data=null, $log_message=null, $properties=array())
    {
        if (!isset($properties['user_id']))
            $properties['user_id'] = $this->getCurrentUser()->id;

        if (is_array($data)) {
            $data = json_encode($data);
        }
        Audit::add($target, $action, $data, $log_message, $properties);
    }

    /**
     * @param $worklist
     * @param $user
     * @param null $display_order
     * @return mixed
     */
    public function addWorklistToUserDisplay($worklist, $user, $display_order = null)
    {
        if (is_null($display_order)) {
            $criteria = new CDbCriteria();
            $criteria->addColumnCondition(array('user_id' => $user->id));
            $criteria->select = 'max(display_order) as maxDisplay';
            $row = $this->getModelForClass('WorklistDisplayOrder')->find($criteria);

            $max_display_order = $row['maxDisplay'];
            $display_order = $max_display_order ? $max_display_order+1 : 1;
        }

        $wdo = $this->getInstanceForClass('WorklistDisplayOrder');
        $wdo->worklist_id = $worklist->id;
        $wdo->user_id = $user->id;
        $wdo->display_order = $display_order;

        return $wdo->save();
    }


    /**
     * @param Worklist $worklist
     * @param null $user
     * @param bool $display
     * @return bool
     * @throws CDbException
     */
    public function createWorklistForUser(Worklist $worklist, $user = null, $display = true)
    {
        if (!$user) {
            $user = $this->getCurrentUser();
        }

        $transaction = $this->startTransaction();

        try {
            $worklist->created_user_id = $user->id;
            $worklist->last_modified_user_id = $user->id;

            // save call must force the parent class to accept the set owner id
            if (!$worklist->save(true, null, true)) {
                // TODO: handle different structure for errors
                throw new Exception("Could not create Worklist.");
            }

            if ($display)
                if (!$this->addWorklistToUserDisplay($worklist, $user))
                    throw new Exception("Could not set new worklist display order.");

            $this->audit(self::$AUDIT_TARGET_MANUAL, 'create',
                array('worklist_id' => $worklist->id, 'owner_id' => $user->id),
                "Worklist created.");

            if ($transaction)
                $transaction->commit();

        }
        catch (Exception $e) {
            $this->addError($e->getMessage());
            if ($transaction)
                $transaction->rollback();
            return false;
        }

        return true;
    }

    /**
     * @param $user
     * @return array
     */
    public function getCurrentManualWorklistsForUser($user)
    {
        $worklists = array();
        foreach ($this->getModelForClass('WorklistDisplayOrder')->with('worklist')->findAll(array(
            'condition' => 'user_id=:uid',
            'order' => 'display_order asc',
            'params' => array(':uid' => $user->id))) as $wdo) {
            $worklists[] = $wdo->worklist;
        }

        return $worklists;
    }

    /**
     * @param $user
     * @return mixed
     */
    public function getAvailableManualWorklistsForUser($user)
    {
        $criteria = new CDbCriteria();
        $criteria->addNotInCondition('id', array_map(
            function($v) {return $v->id;},
            $this->getCurrentManualWorklistsForUser($user)));
        $criteria->addColumnCondition(array('created_user_id' => $user->id));
        $criteria->order = 'created_date desc';

        return $this->getModelForClass('Worklist')->findAll($criteria);
    }

    /**
     *
     * @param $user
     * @param array $worklist_ids
     * @return bool
     * @throws CDbException
     */
    public function setWorklistDisplayOrderForUser($user, $worklist_ids = array())
    {
        $transaction = $this->startTransaction();
        $model = $this->getModelForClass('WorklistDisplayOrder');
        try {
            $model->deleteAllByAttributes(array('user_id' => $user->id));

            if ($worklist_ids) {
                $rows = array();
                foreach ($worklist_ids as $display_order => $worklist_id) {
                    $order = $this->getInstanceForClass('WorklistDisplayOrder');
                    $order->attributes = array(
                        'worklist_id' => $worklist_id,
                        'user_id' => $user->id,
                        'display_order' => $display_order,
                    );
                    if (!$order->save())
                        throw new Exception("Could not save order entry");
                }
            }

            $this->audit(self::$AUDIT_TARGET_MANUAL, 'ordered', array('user_id' => $user->id),
                'Worklists reordered for user.');

            if ($transaction)
                $transaction->commit();
        }
        catch (Exception $e) {
            $this->addError($e->getMessage());
            if ($transaction)
                $transaction->rollback();
            return false;
        }

        return true;
    }

    /**
     * @param Worklist $worklist
     * @param Patient $patient
     * @return array|CActiveRecord|mixed|null
     */
    public function getWorklistPatient(Worklist $worklist, Patient $patient)
    {
        return $this->getModelForClass('WorklistPatient')->findByAttributes(array('patient_id' => $patient->id, 'worklist_id' => $worklist->id));
    }

    /**
     * @param WorklistPatient $worklist_patient
     * @param array $attributes
     * @return bool
     * @throws CDbException
     * @throws Exception
     */
    public function setAttributesForWorklistPatient(WorklistPatient $worklist_patient, $attributes = array())
    {
        $transaction = $this->startTransaction();
        $worklist = $worklist_patient->worklist;

        try {
            $valid_attributes = array();
            foreach ($worklist->mapping_attributes as $attr)
                $valid_attributes[$attr->name] = $attr->id;

            foreach ($attributes as $attr => $val) {
                if (!array_key_exists($attr, $valid_attributes))
                    throw new Exception("Unrecognised attribute {$attr} for {$worklist->name}");
                $wlattr = $this->getInstanceForClass('WorklistPatientAttribute');
                $wlattr->attributes = array(
                    'worklist_patient_id' => $worklist_patient->id,
                    'worklist_attribute_id' => $valid_attributes[$attr],
                    'attribute_value' => $val
                );
                if (!$wlattr->save())
                    throw new Exception("Unable to save attribute {$attr} for patient worklist.");
            }

            if ($transaction)
                $transaction->commit();
        }
        catch (Exception $e)
        {
            $this->addError($e->getMessage());
            if ($transaction)
                $transaction->rollback();
            return false;
        }

        return true;
    }

    /**
     * If the given Patient is successfully added to the given Worklist, returns true. false otherwise
     *
     * @param Patient $patient
     * @param Worklist $worklist
     * @param datetime $when
     * @param array $attributes
     * @return bool
     */
    public function addPatientToWorklist(Patient $patient, Worklist $worklist, $when=null, $attributes = array())
    {
        $this->reset();


        if ($this->getWorklistPatient($worklist, $patient)) {
            $this->addError("Patient is already on the given worklist.");
            return false;
        }

        $transaction = $this->startTransaction();

        try {
            $wp = $this->getInstanceForClass('WorklistPatient');
            $wp->patient_id = $patient->id;
            $wp->worklist_id = $worklist->id;
            if ($when)
                $wp->when = $when;

            if (!$wp->save())
                throw new Exception("Unable to save patient to worklist.");

            if (count($attributes))
                if (!$this->setAttributesForWorklistPatient($wp, $attributes))
                    throw new Exception("Could not set attributes for patient on worklist");

            $this->audit(self::$AUDIT_TARGET_MANUAL, 'add-patient',
                array('worklist_id' => $worklist->id), "Patient added to worklist",
                array('patient_id' => $patient->id));

            if ($transaction)
                $transaction->commit();
        }
        catch (Exception $e) {
            $this->addError($e->getMessage());
            if ($transaction)
                $transaction->rollback();
            return false;
        }

        return true;
    }

    /**
     * @param $worklist
     * @return mixed
     */
    protected function renderWorklistForDashboard($worklist)
    {
        return $this->renderPartial('//worklist/dashboard', array(
                'worklist' => $worklist,
                'worklist_patients' => $this->getPatientsForWorklist($worklist)
            )
        );
    }

    /**
     * @param User|null $user
     * @return array
     */
    public function renderManualDashboard($user = null)
    {
        if (!$user)
            $user = $this->getCurrentUser();

        $content = "";
        foreach ($this->getCurrentManualWorklistsForUser($user) as $worklist) {
            $content .= $this->renderWorklistForDashboard($worklist);
        }

        return array(
            'title' => "Manual Worklists" ,
            'content' => $content,
            'options' => array(
                'container-id' => \Yii::app()->user->id.'-manual-worklists-container',
                'js-toggle-open' => true,
            )
        );
    }

    /**
     *
     * @FIXME: investigate alternate abstractions ... might be some issues here
     * @TODO: test me
     * @param $worklist
     * @param null $limit
     * @param null $offset
     * @return mixed
     */
    public function getPatientsForWorklist($worklist, $limit = null, $offset = null)
    {
        $wp_model = $this->getModelForClass('WorklistPatient');

        $criteria = new CDbCriteria();
        $criteria->addColumnCondition(array("t.worklist_id" => $worklist->id));

        if ($limit)
            $criteria->limit = $limit;
        if ($offset)
            $criteria->offset = $offset;

        if ($worklist->scheduled) {
            $criteria->order = "t.when";
        }
        else {
            $criteria->order = "LOWER(contact.last_name), LOWER(contact.first_name)";
        }

        return $wp_model->with(array('patient','patient.contact'))->findAll($criteria);
    }

    /**
     * Internal method to reset state for error tracking
     */
    protected function reset()
    {
        $this->errors = array();
    }

    /**
     * @param string $message
     */
    protected function addError($message)
    {
        if (!in_array($message, $this->errors))
            $this->errors[] = $message;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }
}