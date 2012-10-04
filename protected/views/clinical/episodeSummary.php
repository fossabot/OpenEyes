<?php
/**
 * OpenEyes
 *
 * (C) Moorfields Eye Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenEyes Foundation, 2011-2012
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields Eye Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2012, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

if (!empty($episode)) {
	if ($episode->diagnosis) {
		$eye = $episode->eye ? $episode->eye->name : 'None';
		$diagnosis = $episode->diagnosis ? $episode->diagnosis->term : 'none';
	} else {
		$eye = 'No diagnosis';
		$diagnosis = 'No diagnosis';
	}

	$audit = new Audit;
	$audit->action = "view";
	$audit->target_type = "episode summary";
	$audit->patient_id = $this->patient->id;
	$audit->episode_id = $episode->id;
	$audit->user_id = (Yii::app()->session['user'] ? Yii::app()->session['user']->id : null);
	$audit->save();
?>
	<h3>Summary</h3>
	<h3 class="episodeTitle"><?php echo $episode->firm->serviceSubspecialtyAssignment->subspecialty->name?></h3>

	<h4>Principal diagnosis:</h4>

	<?php
	$form = $this->beginWidget('BaseEventTypeCActiveForm', array(
			'id'=>'add-systemic-diagnosis',
			'enableAjaxValidation'=>false,
			'htmlOptions' => array('class'=>'sliding'),
			'action'=>array('patient/updateepisode'),
	));

	?><input type="hidden" name="episode_id" value="<?php echo $episode->id?>" /><?php

	if ($episode->editable) {
		$form->widget('application.widgets.DiagnosisSelection',array(
				'field' => 'disorder_id',
				'options' => CommonOphthalmicDisorder::getList(Firm::model()->findByPk($this->selectedFirmId)),
				'restrict' => 'ophthalmic',
				'layout' => 'episodeSummary',
		));

		?><button style="margin-left:10px; margin-bottom: 10px;" class="classy blue mini" type="submit" id="save_episode_diagnosis"><span class="button-span button-span-blue">Change diagnosis</span></button><?php
	} else {?>
		<div class="eventHighlight big">
			<h4 style="font-style: normal; border-top: none;"><?php echo $episode->diagnosis ? $episode->diagnosis->term : 'None'?></h4>
		</div>
	<?php }?>

	<h4 style="font-style: normal; border-top: none;">Principal eye:</h4>

	<?php if ($episode->editable) {?>
		<div>
			<?php foreach (Eye::model()->findAll(array('order'=>'display_order')) as $eye) {?>
				<?php echo CHtml::radioButton('eye_id', $episode->eye_id == $eye->id,array('value' => $eye->id,'class'=>'episodeSummaryRadio'))?>
				<label for="<?php echo $episode->eye_id?>"><?php echo $eye->name?></label>
			<?php }?>
		</div>
		<button style="margin-left:10px; margin-bottom: 15px;" class="classy blue mini" type="submit" id="save_episode_eye"><span class="button-span button-span-blue">Change eye</span></button>
	<?php }else{?>
		<div class="eventHighlight big">
			<h4 style="font-style: normal; border-top: none;"><?php echo $episode->eye ? $episode->eye->name : 'None'?></h4>
		</div>
	<?php }

	$this->endWidget()?>

	<!-- divide into two columns -->
	<div class="cols2 clearfix">
		<div class="left">
			<h4>Start Date</h4>
			<div class="eventHighlight">
				<h4><?php echo $episode->NHSDate('start_date')?></h4>
			</div>
		</div>

		<div class="right">
			<h4>End date:</h4>
			<div class="eventHighlight">
				<h4><?php echo !empty($episode->end_date) ? $episode->end_date : '(still open)'?></h4>
			</div>
		</div>

		<div class="left">
		<h4>Subspecialty:</h4>
			<div class="eventHighlight">
				<h4><?php echo $episode->firm->serviceSubspecialtyAssignment->subspecialty->name?></h4>
			</div>
		</div>

		<div class="right">
		<h4>Consultant firm:</h4>
			<div class="eventHighlight">
				<h4><?php echo $episode->firm->name?></h4>
			</div>
		</div>
	</div> <!-- end of cols2 (column split) -->

	<?php
	try {
		echo $this->renderPartial('/clinical/episodeSummaries/' . $episode->firm->serviceSubspecialtyAssignment->subspecialty_id, array('episode' => $episode));
	} catch (Exception $e) {
		// If there is no extra episode summary detail page for this subspecialty we don't care
	}
} else {
	// hide the episode border ?>
	<script type="text/javascript">
		$('div#episodes_details').hide();
	</script>
<?php }?>

<div class="metaData">
	<span class="info"><?php echo $episode->firm->serviceSubspecialtyAssignment->subspecialty->name?>: created by <span class="user"><?php echo $episode->user->fullName?> on <?php echo $episode->NHSDate('created_date')?> at <?php echo substr($episode->created_date,11,5)?></span></span>
</div>

<!-- Booking -->

<h4>Episode Status</h4>

<div class="eventDetail">
	<div class="label"><?php echo CHtml::encode($episode->getAttributeLabel('episode_status_id'))?></div>
	<div class="data">
		<span class="group">
			<form>
				<?php echo CHtml::dropDownList('episode_status_id', $episode->episode_status_id, EpisodeStatus::Model()->getList())?>
				<button style="margin-left:20px;" class="classy blue mini" type="submit" id="save_episode_status"><span class="button-span button-span-blue">Change status</span></button>
			</form>
		</span>
	</div>
</div>

<div class="metaData">
	<span class="info">Status last changed by <span class="user"><?php echo $episode->usermodified->fullName?> on <?php echo $episode->NHSDate('last_modified_date')?> at <?php echo substr($episode->last_modified_date,11,5)?></span></span>
</div>
<script type="text/javascript">
	$('#closelink').click(function() {
		$('#dialog-confirm').dialog({
			resizable: false,
			height: 140,
			modal: false,
			buttons: {
				"Close episode": function() {
					$.ajax({
						url: $('#closelink').attr('href'),
						type: 'GET',
						success: function(data) {
							$('#episodes_details').show();
							$('#episodes_details').html(data);
						}
					});
					$(this).dialog('close');
				},
				Cancel: function() {
					$(this).dialog('close');
				}
			},
				open: function() {
					$(this).parents('.ui-dialog-buttonpane button:eq(1)').focus();
				}
		});
		return false;
	});
</script>

<?php if (empty($episode->end_date)) {?>
	<div style="margin-top:40px; text-align:right; position:relative; ">
		<!--button id="close-episode" type="submit" value="submit" class="wBtn_close-episode ir">Close Episode</button-->

		<div id="close-episode-popup" class="popup red" style="display: none;">
			<p style="text-align:left;">You are closing this episode. This can not be undone. Once an episode is closed it can not be re-opened.</p>
			<p><strong>Are you sure?</strong></p>
			<div class="action_options">
				<span class="aBtn"><a id="yes-close-episode" href="#"><strong>Yes, I am</strong></a></span>
				<span class="aBtn"><a id="no-close-episode" href="#"><strong>No, cancel this.</strong></a></span>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		$('#close-episode').unbind('click').click(function(e) {
			e.preventDefault();
			$('#close-episode-popup').slideToggle(100);
			return false;
		});

		$('#no-close-episode').unbind('click').click(function(e) {
			e.preventDefault();
			$('#close-episode-popup').slideToggle(100);
			return false;
		});

		$('#yes-close-episode').unbind('click').click(function(e) {
			e.preventDefault();
			$('#close-episode-popup').slideToggle(100);
			$.ajax({
				url: '<?php echo Yii::app()->createUrl('clinical/closeepisode/'.$episode->id)?>',
				success: function(data) {
					$('#event_content').html(data);
					return false;
				}
			});

			return false;
		});

		$('#save_episode_status').unbind('click').click(function(e) {
			if (!$(this).hasClass('inactive')) {
				e.preventDefault();
				disableButtons();

				$.ajax({
					type: 'POST',
					url: '<?php echo Yii::app()->createUrl('patient/setepisodestatus/'.$episode->id)?>',
					data: 'episode_status_id='+$('#episode_status_id').val(),
					success: function(html) {
						window.location.href = '<?php echo Yii::app()->createUrl('patient/episodes/'.$this->patient->id)?>';
					}
				});
			}

			return false;
		});
	</script>
<?php }?>
