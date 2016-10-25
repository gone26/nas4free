<?php
/*
	co_zpool_info.inc

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2016 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies,
	either expressed or implied, of the NAS4Free Project.
*/
require_once 'config.inc';
require_once 'util.inc';

class co_zpool_info {
/* 
 *	object to collect and provide the current zpool configuration.
 *	Several get methods return filtered information from the configuration.
 *	This object uses SimpleXML to store configuration information and
 *	it makes heavily use of xpath.
 * 
 */
	private $a_xml;
	private $poolname_filter;
	private $is_poolname_filter_set;
	private $devicepath_strip_regex;
	private $is_devicepath_strip_regex;
	
	public function __construct() {
		$this->scan();
		$this->set_poolname_filter();
		$this->set_devicepath_strip_regex();
	}
	public function pool_state_to_name($n_state = 0) {
		switch ($n_state) {
			case 0: return gtext('Active');
			case 1: return gtext('Exported');
			case 2: return gtext('Destroyed');
			case 3: return gtext('Spare');
			case 4: return gtext('L2 Cache');
			case 5: return gtext('Uninitialized');
			case 6: return gtext('Unavailable');
			case 7: return gtext('Potentially Active');
		}
		return gtext('Unknown');
	}
	public function set_poolname_filter($value = '') {
		if(preg_match('/\S/', $value)) {
			$this->poolname_filter = $value;
			$this->is_poolname_filter_set = true;
		} else {
			$this->poolname_filter = '';
			$this->is_poolname_filter_set = false;
		}
	}
	public function set_devicepath_strip_regex($value = '') {
		if(preg_match('/\S/', $value)) {
			$this->devicepath_strip_regex = $value;
			$this->is_devicepath_strip_regex = true;
		} else {
			$this->devicepath_strip_regex = '';
			$this->is_devicepath_strip_regex = false;
		}
	}
	protected function pool_info_to_array($r_pool, &$r_ret = []) {
		$r_ret['pool.guid'] = (string)$r_pool->pool_guid;
		$r_ret['pool.name'] = (string)$r_pool->name;
		$r_ret['pool.version'] = (string)$r_pool->version;
		$r_ret['pool.state'] = (string)$r_pool->state;
		$r_ret['pool.state_name'] = $this->pool_state_to_name($r_ret['pool.state']);
		return $r_ret;
	}
	protected function vdev_info_to_array($r_vdev, &$r_ret = []) {
		$r_ret['vdev.type'] = (string)$r_vdev->type;
		$r_ret['vdev.id'] = (string)$r_vdev->id;
		$r_ret['vdev.guid'] = (string)$r_vdev->guid;
		$r_ret['vdev.ashift'] = (string)$r_vdev->ashift;
		$r_ret['vdev.asize'] = (string)$r_vdev->asize;
		$r_ret['vdev.is_log'] = (string)$r_vdev->is_log;
		return $r_ret;
	}
	protected function devicevdev_info_to_array($r_device, &$ret = []) {
		$r_ret['vdev.type'] = (string)$r_device->type;
		$r_ret['vdev.id'] = (string)$r_device->id;
		$r_ret['vdev.guid'] = uuid();
		$r_ret['vdev.ashift'] = (string)$r_device->ashift;
		$r_ret['vdev.asize'] = (string)$r_device->asize;
		$r_ret['vdev.is_log'] = (string)$r_device->is_log;
		return $r_ret;
	}
	protected function device_info_to_array($r_device, &$r_ret = []) {
		$r_ret['device.type'] = (string)$r_device->type;
		$r_ret['device.id'] = (string)$r_device->id;
		$r_ret['device.guid'] = (string)$r_device->guid;
		if($this->is_devicepath_strip_regex) {
			$r_ret['device.path'] = preg_replace($this->devicepath_strip_regex, '', (string)$r_device->path);
		} else {
			$r_ret['device.path'] = (string)$r_device->path;
		}
		$r_ret['device.phys_path'] = (string)$r_device->phys_path;
		return $r_ret;
	}
	public function get_all_pools(&$a_ret = []) {
/*
 *		returns all pools (based on zpool list)
 */
		$r_ret =[];
		$x_pool = '/pools/pool';
		$a_pool = $this->a_xml->xpath($x_pool);
		if(false !== $a_pool) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				$a_ret[(string)$r_pool->pool_guid] = $r_ret;
			}
		}
		return $a_ret;
	}
	public function get_all_data_devices(&$a_ret = []) {
/*
 *		locate all data members and prepare one row per member. return an array of records with disks, vdev and pool information
 */
		$this->get_single_data_devices($a_ret);
		$this->get_mirrored_data_devices($a_ret);
		$this->get_raidz_data_devices($a_ret);
		return $a_ret;
	}
	public function get_single_data_devices(&$a_ret = []) {
/*
 *		locate single data members and prepare one row per member. return an array of records with disks, vdev and pool information
 */
		$r_ret = [];
		if($this->is_poolname_filter_set) { // single pool. sequence: pool -> device
			$x_pool = sprintf('/pools/pool[name="%s"]', $this->poolname_filter); // query pool
		} else {
			$x_pool = '/pools/pool[vdev_tree/children[is_log="0" and type="disk"]]';
		}
		$x_device = './vdev_tree/children[is_log="0" and type="disk"]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				if(false !== ($a_device = $r_pool->xpath($x_device))) {
					foreach($a_device as $r_device) {
						$this->devicevdev_info_to_array($r_device, $ret);
						$this->device_info_to_array($r_device, $r_ret);
						$a_ret[(string)$r_device->guid] = $r_ret;
					}
				}
			}
		}
		return $a_ret;
	}
	public function get_mirrored_data_devices(&$a_ret = []) {
/*
 *		locate mirrored data members and prepare one row per member. return an array of records with disks, vdev and pool
 */
		$r_ret = [];
		if($this->is_poolname_filter_set) { // is a pool filter set?
			$x_pool = sprintf('/pools/pool[name="%s"]', $this->poolname_filter);
		} else {
			$x_pool = '/pools/pool[vdev_tree/children[is_log="0" and type="mirror"]]';
		}
		$x_vdev = './vdev_tree/children[is_log="0" and type="mirror"]';
		$x_device = './/children[is_log="0" and type="disk"]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				if(false !== ($a_vdev = $r_pool->xpath($x_vdev))) {
					foreach($a_vdev as $r_vdev) {
						$this->vdev_info_to_array($r_vdev, $r_ret);
						if(false !== ($a_device = $r_vdev->xpath($x_device))) {
							foreach($a_device as $r_device) {
								$this->device_info_to_array($r_device, $r_ret);
								$a_ret[(string)$r_device->guid] = $r_ret;
							}
						}
					}
				}
			}
		}
		return $a_ret;
	}
	public function get_raidz_data_devices(&$a_ret = []) {
/*
 *		locate raidz data members and prepare one row per member. return an array of records with disks, vdev and pool
 */
		$r_ret = [];
		if($this->is_poolname_filter_set) { // is a pool filter set?
			$x_pool = sprintf('/pools/pool[name="%s"]', $this->poolname_filter);
		} else {
			$x_pool = '/pools/pool[vdev_tree/children[is_log="0" and type="raidz"]]';
		}
		$x_vdev = './vdev_tree/children[is_log="0" and type="raidz"]';
		$x_device = './/children[is_log="0" and type="disk"]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				if(false !== ($a_vdev = $r_pool->xpath($x_vdev))) {
					foreach($a_vdev as $r_vdev) {
						$this->devicevdev_info_to_array($r_device, $ret);
						if(false !== ($a_device = $r_vdev->xpath($x_device))) {
							foreach($a_device as $r_device) {
								$this->device_info_to_array($r_device, $r_ret);
								$a_ret[(string)$r_device->guid] = $r_ret;
							}
						}
					}
				}
			}
		}
		return $a_ret;
	}
	public function get_all_log_devices(&$a_ret = []) {
		$this->get_single_log_devices($a_ret);
		$this->get_mirrored_log_devices();
		return $a_ret;
	}
	public function get_single_log_devices(&$a_ret = []) {
/*
 *		locate single log members and prepare one row per member. return an array of records with disks, vdev and pool
 */
		$r_ret = [];
		if($this->is_poolname_filter_set) { // single pool. sequence: pool -> device
			$x_pool = sprintf('/pools/pool[name="%s"]', $this->poolname_filter); // query pool
		} else {
			$x_pool = '/pools/pool[vdev_tree/children[is_log="1" and type="disk"]]';
		}
		$x_device = './vdev_tree/children[is_log="1" and type="disk"]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				if(false !== ($a_device = $r_pool->xpath($x_device))) {
					foreach($a_device as $r_device) {
						$this->devicevdev_info_to_array($r_device, $ret);
						$this->device_info_to_array($r_device, $r_ret);
						$a_ret[(string)$r_device->guid] = $r_ret;
					}
				}
			}
		}
		return $a_ret;
	}
	public function get_mirrored_log_devices(&$a_ret = []) {
/*
 *		locate mirrored log members and prepare one row per member. return an array of records with disks, vdev and pool
 */
		$r_ret = [];
		if($this->is_poolname_filter_set) { // is a pool filter set?
			$x_pool = sprintf('/pools/pool[name="%s"]', $this->poolname_filter);
		} else {
			$x_pool = '/pools/pool[vdev_tree/children[is_log="1" and type="mirror"]]';
		}
		$x_vdev = './vdev_tree/children[is_log="1" and type="mirror"]';
		$x_device = './/children[is_log="1" and type="disk"]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				if(false !== ($a_vdev = $r_pool->xpath($x_vdev))) {
					foreach($a_vdev as $r_vdev) {
						$this->devicevdev_info_to_array($r_device, $ret);
						if(false !== ($a_device = $r_vdev->xpath($x_device))) {
							foreach($a_device as $r_device) {
								$this->device_info_to_array($r_device, $r_ret);
								$a_ret[(string)$r_device->guid] = $r_ret;
							}
						}
					}
				}
			}
		}
		return $a_ret;
	}
	public function get_all_cache_devices(&$a_ret = []) {
		$this->get_single_cache_devices($a_ret);
		return $a_ret;
	}
	public function get_single_cache_devices(&$a_ret = []) {
/*
 *		locate single cache members and prepare one row per member. return an array of records with disks and pool
 */
		$r_ret = [];
		if($this->is_poolname_filter_set) {
			$x_pool = sprintf('/pools/pool[name="%s"]', $this->poolname_filter);
		} else {
			$x_pool = '/pools/pool[caches]';
		}
		$x_device = './caches/.//children[is_cache="1"]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				if(false !== ($a_device = $r_pool->xpath($x_device))) {
					foreach($a_device as $r_device) {
						$this->devicevdev_info_to_array($r_device, $ret);
						$this->device_info_to_array($r_device, $r_ret);
						$a_ret[(string)$r_device->guid] = $r_ret;
					}
				}
			}
		}
		return $a_ret;
	}
	public function get_all_spare_devices(&$a_ret = []) {
		$this->get_single_spare_devices($a_ret);
		return $a_ret;
	}
	public function get_single_spare_devices(&$a_ret = []) {
/*
 *		locate single spare members and prepare one row per member. return an array of records with disks, vdev and pool
 */
		$r_ret = [];
		if($this->is_poolname_filter_set) {
			$x_pool = sprintf('/pools/pool[name="%s"]', $this->poolname_filter);
		} else {
			$x_pool = '/pools/pool[spares]';
		}
		$x_device = './spares/.//children[is_spare="1"]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				if(false !== ($a_device = $r_pool->xpath($x_device))) {
					foreach($a_device as $r_device) {
						$this->devicevdev_info_to_array($r_device, $ret);
						$this->device_info_to_array($r_device, $r_ret);
						$a_ret[(string)$r_device->guid] = $r_ret;
					}
				}
			}
		}
		return $a_ret;
	}
	public function get_all_devices(&$a_ret = []) {
		$this->get_all_cache_devices($a_ret);
		$this->get_all_data_devices($a_ret);
		$this->get_all_log_devices($a_ret);
		$this->get_all_spare_devices($a_ret);
		return $a_ret;
	}
	public function get_all_devices_except_spare_devices(&$a_ret = []) {
		$this->get_all_cache_devices($a_ret);
		$this->get_all_data_devices($a_ret);
		$this->get_all_log_devices($a_ret);
		return $a_ret;
	}
	public function get_pools_with_single_data_devices(&$a_ret = []) {
		$r_ret = [];
		$x_pool = '/pools/pool[vdev_tree/children[is_log="0" and type="disk"]]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				$a_ret[(string)$r_pool->pool_guid] = $r_ret;
			}
		}
		return $a_ret;
	}
	public function get_pools_with_mirrored_data_devices(&$a_ret = []) {
		$r_ret = [];
		$x_pool = '/pools/pool[vdev_tree/children[is_log="0" and type="mirror"]]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				$a_ret[(string)$r_pool->pool_guid] = $r_ret;
			}
		}
		return $a_ret;
	}
	public function get_pools_with_raidz_data_devices(&$a_ret = []) {
		$r_ret = [];
		$x_pool = '/pools/pool[vdev_tree/children[is_log="0" and type="raidz"]]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				$a_ret[(string)$r_pool->pool_guid] = $r_ret;
			}
		}
		return $a_ret;
	}
	public function get_pools_with_cache_devices(&$a_ret = []) {
		$this->get_pools_with_single_cache_devices($a_ret);
		return $a_ret;
	}
	public function get_pools_with_single_cache_devices(&$a_ret = []) {
		$r_ret = [];
		$x_pool = '/pools/pool[caches/children[is_cache="1" and type="disk"]]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				$a_ret[(string)$r_pool->pool_guid] = $r_ret;
			}
		}
		return $a_ret;
	}
	public function get_pools_with_log_devices(&$a_ret = []) {
		$this->get_pools_with_mirrored_log_devices($a_ret);
		$this->get_pools_with_single_log_devices($a_ret);
		return $a_ret;
	}
	public function get_pools_with_single_log_devices(&$a_ret = []) {
		$r_ret = [];
		$x_pool = '/pools/pool[vdev_tree/children[is_log="1" and type="disk"]]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				$a_ret[(string)$r_pool->pool_guid] = $r_ret;
			}
		}
		return $a_ret;
	}
	public function get_pools_with_mirrored_log_devices(&$a_ret = []) {
		$r_ret = [];
		$x_pool = '/pools/pool[vdev_tree/children[is_log="1" and type="mirror"]]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				$a_ret[(string)$r_pool->pool_guid] = $r_ret;
			}
		}
		return $a_ret;
	}
	public function get_pools_with_spare_devices(&$a_ret = []) {
		$this->get_pools_with_single_spare_devices($a_ret);
		return $a_ret;
	}
	public function get_pools_with_single_spare_devices(&$a_ret = []) {
		$r_ret = [];
		$x_pool = '/pools/pool[spares/children[is_spare="1"]]';
		if(false !== ($a_pool = $this->a_xml->xpath($x_pool))) {
			foreach($a_pool as $r_pool) {
				$this->pool_info_to_array($r_pool, $r_ret);
				$a_ret[(string)$r_pool->pool_guid] = $r_ret;
			}
		}
		return $a_ret;
	}
	public function get_pools_for_attach_data(&$a_ret = []) {
		$this->get_pools_with_single_data_devices($a_ret);
		$this->get_pools_with_mirrored_data_devices($a_ret);
		return $a_ret;
	}
	public function get_pool_devices_for_attach_data(&$a_ret = []) {
		$this->get_single_data_devices($a_ret);
		$this->get_mirrored_data_devices($a_ret);
		return $a_ret;
	}

	public function get_pools_for_attach_log(&$a_ret = []) {
		$this->get_pools_with_single_log_devices($a_ret);
		$this->get_pools_with_mirrored_log_devices($a_ret);
		return $a_ret;
	}
	public function get_pool_devices_for_attach_log(&$a_ret = []) {
		$this->get_single_log_devices($a_ret);
		$this->get_mirrored_log_devices($a_ret);
		return $a_ret;
	}

	public function get_pools_for_offline_data(&$a_ret = []) {
		$this->get_pools_with_mirrored_data_devices($a_ret);
		$this->get_pools_with_raidz_data_devices($a_ret);
		return $a_ret;
	}
	public function get_pool_devices_for_offline_data(&$a_ret = []) {
		$this->get_mirrored_data_devices($a_ret);
		$this->get_raidz_data_devices($a_ret);
		return $a_ret;
	}

	public function get_pools_for_online_data(&$a_ret = []) {
		$this->get_pools_with_mirrored_data_devices($a_ret);
		$this->get_pools_with_raidz_data_devices($a_ret);
		return $a_ret;
	}
	public function get_pool_devices_for_online_data(&$a_ret = []) {
		$this->get_mirrored_data_devices($a_ret);
		$this->get_raidz_data_devices($a_ret);
		return $a_ret;
	}
	public function get_pools_for_replace_data(&$a_ret = []) {
		$this->get_pools_with_mirrored_data_devices($a_ret);
		$this->get_pools_with_raidz_data_devices($a_ret);
		return $a_ret;
	}
	public function get_pool_devices_for_replace_data(&$a_ret = []) {
		$this->get_mirrored_data_devices($a_ret);
		$this->get_raidz_data_devices($a_ret);
		return $a_ret;
	}
	public function scan() {
		$tag_open = [];
		$tag_index = -1;
		$c_xml = '';
		$c_xml .= '<?xml version="1.0" encoding="UTF-8"?>';
		$c_xml .= sprintf('<%s>', 'pools'); // everything starts with pools
		$ap_pool = cli_zpool_info('', 'name,guid,health'); // get all available pools
		foreach($ap_pool as $rp_pool) {
			// get structure of each pool (zdb lists data vdevs and log vdevs but not spare and cache devices)
			$cmd = sprintf('/usr/sbin/zdb -C %s', escapeshellarg($rp_pool['name']));
			$a_row = [];
			$retval = 0;
			mwexec2($cmd, $a_row, $retval);
			if($retval === 0) { // zdb command successful, pool information is available
				$c_xml .= sprintf('<%s>', 'pool'); // open pool tag
				$indent_previous = 0;
				foreach($a_row as $r_row) {
					$tag = '';
					$b_tag = false;
					$val = '';
					$b_val = false;
//					if(!$b_tag) {
						unset($a_matches);
						if(preg_match_all('/^(\s+)([^\s:]+):$/', $r_row, $a_matches)) { // tag detected
							$indent_current = strlen($a_matches[1][0]);
							$tag = $a_matches[2][0];
							$val = '';
							$b_tag = true;
						}
//					}
					if(!$b_tag) { // no tag without value detected, now check for tag:value
						unset($a_matches);
						if(preg_match_all('/^(\s+)([^\s:]+):\s(\S.*)$/i', $r_row, $a_matches)) { // tag->value detected)
							$indent_current = strlen($a_matches[1][0]);
							$tag = $a_matches[2][0];
							$val = str_replace("'", "", trim($a_matches[3][0])); // trim and remove surrounding single quores from value
							$b_val = true;
						}
					}
					if($b_tag || $b_val) {
						// remove [(number)] from tag
						unset($a_matches);
						if(preg_match_all('/(\S+)\[\d\]/', $tag, $a_matches)) { // no start tag required, already covered
							$tag = $a_matches[1][0];
						}
						switch ($indent_current <=> $indent_previous) {
							case -1: { // level up, we need to close some tags before we process the line
								// determine how many levels must be closed
								$indent_diff = intdiv(($indent_previous - $indent_current), 4);
								for($index = 0; $index < $indent_diff; $index++) {
									$c_xml .= sprintf('</%s>', $tag_open[$tag_index]);
									unset($tag_open[$tag_index]);
									$tag_index--;
								}
								if($b_val) { // it's a tag:value pair
									$c_xml .= sprintf('<%s>%s</%s>', $tag, $val, $tag);
								} else {  // it's a new object
									$tag_index++;
									$tag_open[$tag_index] = $tag;
									$c_xml .= sprintf('<%s>', $tag);
								}
								$indent_previous = $indent_current;
								break;
							}
							case 0: { // same level, check for new objects / tag -> value, create tags
								if($b_val) { // it's a tag:value pair
									$c_xml .= sprintf('<%s>%s</%s>', $tag, $val, $tag);
								} else { // it's a new object
									$tag_index++;
									$tag_open[$tag_index] = $tag;
									$c_xml .= sprintf('<%s>', $tag);
								}
								$indent_previous = $indent_current;
								break;
							}
							case 1: {
								if($b_val) { // it's a tag:value pair
									$c_xml .= sprintf('<%s>%s</%s>', $tag, $val, $tag);
								} else { // it's a new object
									$tag_open[$tag_index] = $tag;
									$tag_index++;
									$c_xml .= sprintf('<%s>', $tag);
								}
								$indent_previous = $indent_current;
								break;
							}
						}
					}
				}
				// close tags
				for($index = $tag_index; $index > -1; $index--) {
					$c_xml .= sprintf('</%s>', $tag_open[$tag_index]);
					unset($tag_open[$tag_index]);
					$tag_index--;
				}
				// read pool status and add additional information to the pool
				$cmd = sprintf('zpool status %s', escapeshellarg($rp_pool['name']));
				$a_row = [];
				$retval = 0;
				mwexec2($cmd, $a_row, $retval);
				// add spares
				$c_xml .= sprintf('<%s>', 'spares');
				$a_spare = [];
				$n_spare = 0;
				foreach($a_row as $r_row) {
					$a_match = [];
					if (preg_match_all('/^\t(\s*)spares$/', $r_row, $a_match)) {
						$n_spare = strlen($a_match[1][0]) + 2;
						continue;
					}
					if(0 < $n_spare) { // read all lines until indent no longer matches
						$a_match = [];
						if (preg_match_all('/^\t(\s+)(\S+)/', $r_row, $a_match)) {
							if($n_spare === strlen($a_match[1][0])) {
								$a_spare[] = $a_match[2][0];
							}
						} else {
							break;
						}
					}
				}
				foreach($a_spare as $r_spare) { // it is not confirmed that the spare device is a disk
					$c_xml .= '<children>';
					$c_xml .= sprintf('<type>%s</type>', 'disk');
					$c_xml .= sprintf('<guid>%s</guid>', uuid()); // temporary ID
					$c_xml .= sprintf('<path>%s%s</path>', '/dev/', $r_spare); // /dev/ is an assumption
					$c_xml .= sprintf('<phys_path>%s%s</phys_path>', '/dev/',$r_spare);
					$c_xml .= sprintf('<is_spare>%s</is_spare>', '1');
					$c_xml .= '</children>';
				}
				$c_xml .= sprintf('</%s>', 'spares');
				// add cache
				$c_xml .= sprintf('<%s>', 'caches');
				$a_cache = [];
				$n_cache = 0;
				foreach($a_row as $r_row) {
					$a_match = [];
					if (preg_match_all('/^\t(\s*)cache$/', $r_row, $a_match)) {
						$n_cache = strlen($a_match[1][0]) + 2;
						continue;
					}
					if(0 < $n_cache) { // read all lines until indent no longer matches
						$a_match = [];
						if (preg_match_all('/^\t(\s+)(\S+)/', $r_row, $a_match)) {
							if($n_cache === strlen($a_match[1][0])) {
								$a_cache[] = $a_match[2][0];
							}
						} else {
							break;
						}
					}
				}
				foreach($a_cache as $r_cache) { // it is not confirmed that the cache device is a disk
					$c_xml .= '<children>';
					$c_xml .= sprintf('<type>%s</type>', 'disk');
					$c_xml .= sprintf('<guid>%s</guid>', uuid()); // temporary ID
					$c_xml .= sprintf('<path>%s%s</path>', '/dev/', $r_cache);
					$c_xml .= sprintf('<phys_path>%s%s</phys_path>', '/dev/',$r_cache);
					$c_xml .= sprintf('<is_cache>%s</is_cache>', '1');
					$c_xml .= sprintf('<x_type>%s</x_type>', 'CACHE');
					$c_xml .= '</children>';
				}
				$c_xml .= sprintf('</%s>', 'caches'); // end tag of cache
				$c_xml .= sprintf('</%s>', 'pool'); // end tag of pool
			}
		}
		$c_xml .= sprintf('</%s>', 'pools'); // final end tag
		$this->a_xml = new SimpleXMLElement($c_xml);
/*
 * adjust some data 
 */
		// flags (there's only one flag per child):
		// is_log is "1": log vdev or single log disk
		// is_log is "0": data vdev or single data disk
		// is_cache is "1": single cache  disk
		// is_spare is "1": single spare disk
		// 
		// vdev_tree: set is_log = 0 on all vdev that are not log vdevs or single log disks
		$x_device = '/pools/pool/vdev_tree/children[is_log="0"]/.//children[not(is_log)]';		
		$a_device = $this->a_xml->xpath($x_device);
		if(false !== $a_device) {
			foreach($a_device as $r_device) {
				$r_device->addChild('is_log', '0');
			}
		}
		// vdev_tree: set is_log = 1 on all vdev that are log vdevs or single log disks
		$x_device = '/pools/pool/vdev_tree/children[is_log="1"]/.//children[not(is_log)]';		
		$a_device = $this->a_xml->xpath($x_device);
		if(false !== $a_device) {
			foreach($a_device as $r_device) {
				$r_device->addChild('is_log', '1');
			}
		}
		return true;
	}
}
