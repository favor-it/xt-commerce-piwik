<?php
/**
 * Piwik Analytics Plugin for xt:Commerce - Tracking Code Integration
 *
 * This file is part of Piwik Analytics Plugin.
 *
 * Piwik Analytics Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Piwik Analytics Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Piwik Analytics Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   xt:Commerce Plugin
 * @package    Piwik Analytics
 * @author     Daniel Schumacher <info@favor-it.net>
 * @copyright  2014 Daniel Schumacher
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @link       http://www.favor-it.net/xtcommerce-plugins/piwik-analytics/
 *      
 */
defined ( '_VALID_CALL' ) or die ( 'Direct Access is not allowed.' );

if (PIWIK_ANALYTICS_STATUS == 'true') {
	include _SRV_WEBROOT . _SRV_WEB_PLUGINS . 'piwik_analytics/classes/class.piwik_analytics.php';
	$pa = new piwikAnalytics ();
	$pa->getPiwikCode ();
}
?>