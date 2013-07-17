<?php
/*
Cogumelo v0.2 - Innoto S.L.
Copyright (C) 2010 Innoto Gestión para el Desarrollo Social S.L. <mapinfo@map-experience.com>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,
USA.
*/

//
// Connection Superclass
//

abstract class Connection
{
	public static function factory($devel_data = false)
	{

		$class = 'c_model/'. DB_ENGINE . '/'. ucfirst(DB_ENGINE) ."Connection";
		Cogumelo::load($class);
		
		$dbObj = ucfirst(DB_ENGINE)."Connection";
		return new $dbObj($devel_data);
	}
}
