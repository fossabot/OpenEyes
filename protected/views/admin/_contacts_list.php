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

?>
<div id="searchResults" class="box admin">
	<h2>Contacts</h2>
	<form id="admin_contacts">
		<table class="grid">
			<thead>
				<tr>
					<th>ID</th>
					<th>Title</th>
					<th>First name</th>
					<th>Last name</th>
					<th>Qualifications</th>
					<th>Label</th>
				</tr>
			</thead>
			<tbody>
				<?php
                foreach ($contacts['contacts'] as $i => $contact) {
                    ?>
					<tr class="clickable" data-id="<?php echo $contact->id?>" data-uri="admin/editContact?contact_id=<?php echo $contact->id?>">
						<td><?php echo $contact->id?></td>
						<td><?php echo $contact->title?></td>
						<td><?php echo $contact->first_name?></td>
						<td><?php echo $contact->last_name?></td>
						<td><?php echo $contact->qualifications?></td>
						<td><?php echo $contact->label ? $contact->label->name : 'None'?></td>
					</tr>
				<?php 
                }?>
			</tbody>
			<tfoot class="pagination-container">
				<tr>
					<td colspan="6" class="clearfix">
						<?php echo EventAction::button('Add', 'add', null, array('class' => 'small'))->toHtml()?>
						<?php echo $this->renderPartial('//admin/_pagination', array(
                            'pagination' => $contacts['pagination'],
                        ))?>
					</td>
				</tr>
			</tfoot>
		</table>
	</form>
</div>
