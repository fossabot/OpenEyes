<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2013
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2013, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */
?>
<div class="box admin">
	<header class="box-header">
		<h2 class="box-title">Letter warning rules</h2>
		<div class="box-actions">
			<?php echo EventAction::button('Add', 'add_letter_contact_rule', null, array('class' => 'button small'))->toHtml()?>
		</div>
	</header>

	<form id="rulestest" class="panel">
		<fieldset>
			<legend>
				Test:
			</legend>
			<div class="row field-row">
				<div class="large-2 column">
					<?php echo CHtml::dropDownList('lcr_rule_type_id','',CHtml::listData(OphTrOperationbooking_Admission_Letter_Warning_Rule_Type::model()->findAll(),'id','name'),array('empty'=>'- Rule -'))?>
				</div>
				<div class="large-2 column">
					<?php echo CHtml::dropDownList('lcr_site_id','',Site::model()->getListForCurrentInstitution('name'),array('empty'=>'- Site -'))?>
				</div>
				<div class="large-2 column">
					<?php echo CHtml::dropDownList('lcr_subspecialty_id','',CHtml::listData(Subspecialty::model()->findAllByCurrentSpecialty(),'id','name'),array('empty'=>'- Subspecialty -'))?>
				</div>
				<div class="large-2 column">
					<?php echo CHtml::dropDownList('lcr_firm_id','',array(),array('empty'=>'- Firm -'))?>
				</div>
				<div class="large-2 column">
					<?php echo CHtml::dropDownList('lcr_theatre_id','',array(),array('empty'=>'- Theatre -'))?>
				</div>
				<div class="large-2 column">
					<?php echo CHtml::dropDownList('lcr_is_child','',array('' => '- Child/adult -','1' => 'Child','0' => 'Adult'))?>
				</div>
			</div>
		</fieldset>
	</form>

	<div id="nomatch" class="alert-box alert hide">No match</div>

	<form id="rules" class="panel">
		<?php
		$this->widget('CTreeView',array(
				'data' => $data,
			))?>
	</form>
</div>
