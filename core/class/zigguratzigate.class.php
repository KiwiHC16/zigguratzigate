<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

/*
 * One equipment = one ziggurat-zigate bridge (one radio, one network, one Abeille gateway).
 * The plugin daemon is the set of bridge processes of the enabled equipments.
 */
class zigguratzigate extends eqLogic {

	/*     * ***********************Methode static*************************** */

	public static function binary() {
		return rtrim(config::byKey('binDir', 'zigguratzigate', '/usr/local/bin'), '/') . '/ziggurat-zigate';
	}

	/* Network state files (network key, frame counters): plugin data folder, never in git */
	public static function dataDir() {
		return realpath(dirname(__FILE__) . '/../..') . '/data';
	}

	public static function pidFile($_id) {
		return jeedom::getTmpFolder('zigguratzigate') . '/bridge' . $_id . '.pid';
	}

	public static function logName($_id) {
		return 'zigguratzigate_' . $_id;
	}

	/* Pid of a running bridge, or 0 */
	public static function runningPid($_id) {
		$pidFile = self::pidFile($_id);
		if (!file_exists($pidFile)) {
			return 0;
		}
		$pid = intval(trim(file_get_contents($pidFile)));
		if ($pid <= 0 || !file_exists('/proc/' . $pid . '/cmdline')) {
			return 0;
		}
		// Pid reused by another program?
		if (strpos(file_get_contents('/proc/' . $pid . '/cmdline'), 'ziggurat-zigate') === false) {
			return 0;
		}
		return $pid;
	}

	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = jeedom::getTmpFolder('zigguratzigate') . '/dependance';
		$return['state'] = is_executable(self::binary()) ? 'ok' : 'nok';
		return $return;
	}

	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => dirname(__FILE__) . '/../../resources/install.sh ' . jeedom::getTmpFolder('zigguratzigate') . '/dependance ' . escapeshellarg(self::binary()), 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	public static function deamon_info() {
		$return = array();
		$return['log'] = 'zigguratzigate';
		$return['launchable'] = 'ok';
		$enabled = array();
		foreach (eqLogic::byType('zigguratzigate') as $eqLogic) {
			if ($eqLogic->getIsEnable() == 1) {
				$enabled[] = $eqLogic;
			}
		}
		if (!is_executable(self::binary())) {
			$return['launchable'] = 'nok';
			$return['launchable_message'] = __('ziggurat-zigate introuvable : ', __FILE__) . self::binary();
		} else if (count($enabled) == 0) {
			$return['launchable'] = 'nok';
			$return['launchable_message'] = __('Aucune passerelle activée', __FILE__);
		}
		$return['state'] = (count($enabled) > 0) ? 'ok' : 'nok';
		foreach ($enabled as $eqLogic) {
			if (self::runningPid($eqLogic->getId()) == 0) {
				$return['state'] = 'nok';
			}
		}
		return $return;
	}

	public static function deamon_start() {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		foreach (eqLogic::byType('zigguratzigate') as $eqLogic) {
			if ($eqLogic->getIsEnable() == 1) {
				$eqLogic->startBridge();
			}
		}
		$i = 0;
		while ($i < 15) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 15) {
			log::add('zigguratzigate', 'error', __('Une passerelle ne démarre pas, vérifiez son log', __FILE__), 'unableStartDeamon');
			return false;
		}
		message::removeAll('zigguratzigate', 'unableStartDeamon');
		log::add('zigguratzigate', 'info', 'Passerelles lancées');
		return true;
	}

	public static function deamon_stop() {
		foreach (eqLogic::byType('zigguratzigate') as $eqLogic) {
			$eqLogic->stopBridge();
		}
	}

	/* Running state of each bridge, every 5 minutes */
	public static function cron5() {
		foreach (eqLogic::byType('zigguratzigate', true) as $eqLogic) {
			$eqLogic->checkAndUpdateCmd('running', (self::runningPid($eqLogic->getId()) > 0) ? 1 : 0);
		}
	}

	/*     * *********************Methode d'instance************************* */

	public function getStateFile() {
		$file = trim($this->getConfiguration('stateFile', ''));
		if ($file == '') {
			$file = self::dataDir() . '/bridge' . $this->getId() . '.json';
		}
		return $file;
	}

	public function preSave() {
		$defaults = array(
			'device' => '/dev/ttyUSB0',
			'baudrate' => '460800',
			'flowControl' => 'software',
			'listen' => '127.0.0.1:20108',
			'channel' => '25',
			'txPower' => '8',
			'warmUp' => '35',
		);
		foreach ($defaults as $key => $value) {
			if (trim($this->getConfiguration($key, '')) == '') {
				$this->setConfiguration($key, $value);
			}
		}
	}

	public function postSave() {
		$cmd = $this->getCmd('info', 'running');
		if (!is_object($cmd)) {
			$cmd = new zigguratzigateCmd();
			$cmd->setLogicalId('running');
			$cmd->setName(__('Démarrée', __FILE__));
			$cmd->setIsVisible(1);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setType('info');
		$cmd->setSubType('binary');
		$cmd->save();
	}

	public function preRemove() {
		$this->stopBridge();
	}

	public function commandLine() {
		$cmd = escapeshellarg(self::binary());
		$cmd .= ' --listen ' . escapeshellarg($this->getConfiguration('listen'));
		$cmd .= ' run';
		$cmd .= ' --device ' . escapeshellarg($this->getConfiguration('device'));
		$cmd .= ' --baudrate ' . intval($this->getConfiguration('baudrate'));
		$cmd .= ' --flow-control ' . escapeshellarg($this->getConfiguration('flowControl'));
		$cmd .= ' --state ' . escapeshellarg($this->getStateFile());
		$cmd .= ' --channel ' . intval($this->getConfiguration('channel'));
		$cmd .= ' --tx-power ' . intval($this->getConfiguration('txPower'));
		$cmd .= ' --warm-up ' . intval($this->getConfiguration('warmUp'));
		if (trim($this->getConfiguration('capture', '')) != '') {
			$cmd .= ' --capture ' . escapeshellarg(trim($this->getConfiguration('capture')));
		}
		if ($this->getConfiguration('allowErase', 0) == 1) {
			$cmd .= ' --allow-erase';
		}
		return $cmd;
	}

	public function startBridge() {
		if (self::runningPid($this->getId()) > 0) {
			return;
		}
		$device = $this->getConfiguration('device');
		// Simulated radio: wait for ziggurat-sim to listen
		if (strpos($device, 'tcp://') === 0) {
			$hostPort = explode(':', substr($device, 6));
			$i = 0;
			while ($i < 30) {
				$socket = @fsockopen($hostPort[0], intval($hostPort[1]), $errno, $errstr, 1);
				if ($socket !== false) {
					fclose($socket);
					break;
				}
				sleep(1);
				$i++;
			}
			if ($i >= 30) {
				log::add('zigguratzigate', 'warning', $this->getHumanName() . ' : ' . $device . ' ne répond pas, lancement quand même');
			}
		}
		$dir = dirname($this->getStateFile());
		if (!file_exists($dir)) {
			mkdir($dir, 0700, true);
		}
		$level = log::convertLogLevel(log::getLogLevel('zigguratzigate'));
		$rustLog = ($level == 'debug') ? 'info,ziggurat_zigate=debug' : 'info';
		$cmd = 'RUST_LOG=' . $rustLog . ' NO_COLOR=1 nohup setsid ' . $this->commandLine();
		$cmd .= ' >> ' . log::getPathToLog(self::logName($this->getId())) . ' 2>&1 & echo $!';
		log::add('zigguratzigate', 'info', $this->getHumanName() . ' : lancement ' . $cmd);
		$pid = intval(trim(shell_exec($cmd)));
		file_put_contents(self::pidFile($this->getId()), $pid);
		$this->checkAndUpdateCmd('running', ($pid > 0) ? 1 : 0);
	}

	public function stopBridge() {
		$pid = self::runningPid($this->getId());
		if ($pid > 0) {
			log::add('zigguratzigate', 'info', $this->getHumanName() . ' : arrêt (pid ' . $pid . ')');
			posix_kill($pid, 15);
			$i = 0;
			while ($i < 10 && file_exists('/proc/' . $pid)) {
				usleep(500000);
				$i++;
			}
			if (file_exists('/proc/' . $pid)) {
				posix_kill($pid, 9);
			}
		}
		@unlink(self::pidFile($this->getId()));
		if ($this->getIsEnable() == 1) {
			$this->checkAndUpdateCmd('running', 0);
		}
	}
}

class zigguratzigateCmd extends cmd {

	public function execute($_options = array()) {
	}
}
