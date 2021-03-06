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
 * +----+---------------------+-----------------------+---------------------+-----------------+---------------------+-------+
  | id | name                | last_modified_user_id | last_modified_date  | created_user_id | created_date        | order |
  +----+---------------------+-----------------------+---------------------+-----------------+---------------------+-------+
  |  1 | New                 |                     1 | 1901-01-01 00:00:00 |               1 | 1901-01-01 00:00:00 |     1 |
  |  2 | Under investigation |                     1 | 1901-01-01 00:00:00 |               1 | 1901-01-01 00:00:00 |     2 |
  |  3 | Listed/booked       |                     1 | 1901-01-01 00:00:00 |               1 | 1901-01-01 00:00:00 |     3 |
  |  4 | Post-op             |                     1 | 1901-01-01 00:00:00 |               1 | 1901-01-01 00:00:00 |     4 |
  |  5 | Follow-up           |                     1 | 1901-01-01 00:00:00 |               1 | 1901-01-01 00:00:00 |     5 |
  |  6 | Discharged          |                     1 | 1901-01-01 00:00:00 |               1 | 1901-01-01 00:00:00 |     6 |
  +----+---------------------+-----------------------+---------------------+-----------------+---------------------+-------+

 */
return array(
	 'episodestatus1' => array(
		  'id' => 1,
		  'name' => 'New',
		  'order' => 1,
	 ),
	 'episodestatus2' => array(
		  'id' => 2,
		  'name' => 'Under investigation' ,
		  'order' => 2,
	 ),
	 'episodestatus3' => array(
		  'id' => 3,
		  'name' => 'Listed/booked',
		  'order' => 3,
	 ),
	 'episodestatus4' => array(
		  'id' => 4,
		  'name' => 'Post-op',
		  'order' => 4,
	 ),
	 'episodestatus5' => array(
		  'id' => 5,
		  'name' => 'Follow-up',
		  'order' => 5,
	 ),
	 'episodestatus6' => array(
		  'id' => 6,
		  'name' => 'Discharged',
		  'order' => 6,
	 ),
);
