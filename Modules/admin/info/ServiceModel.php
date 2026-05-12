<?php

class ServiceModel
{
	private $redis;
	private $log;
	private $settings;

	public function __construct($redis = null, $log = null, $settings = array())
	{
		$this->redis = $redis;
		$this->log = $log;
		$this->settings = $settings;
	}

	public function getServicesList()
	{
		return array('emonhub', 'mqtt_input', 'emoncms_mqtt', 'feedwriter', 'service-runner', 'emonPiLCD', 'redis-server', 'mosquitto', 'demandshaper', 'emoncms_sync');
	}

	private function serviceStatus($name)
	{
		$output = false;
		if (function_exists('exec')) {
			@exec('systemctl show ' . escapeshellarg($name) . ' | grep State', $output);
		}
		return $output;
	}

	public function getServiceStatus($name)
	{
		// Validate service name
		$service_name = str_replace('.service', '', $name);

		if (file_exists('/.dockerenv')) {
			if (file_exists('/opt/openenergymonitor/emoncms_pre.sh')) {
				$container_services = array('emoncms_mqtt', 'feedwriter', 'service-runner', 'redis-server', 'mosquitto', 'emoncms_sync');
				if (in_array($service_name, $container_services, true)) {
					return array(
						'LoadState' => 'loaded',
						'ActiveState' => 'active',
						'SubState' => 'running',
						'UnitFileState' => 'container',
					);
				}
			}

			return array();
		}

		if (!in_array($service_name, $this->getServicesList(), true)) {
			return array();
		}

		if (!$service_status = $this->serviceStatus($name)) {
			return array();
		}
		$status = array();

		foreach ($service_status as $line) {
			$parts = explode('=', $line, 2);
			$status[$parts[0]] = $parts[1];
		}

		$return = array();
		$keys = array('LoadState', 'ActiveState', 'SubState', 'UnitFileState');
		foreach ($keys as $key) {
			if (isset($status[$key])) {
				$return[$key] = $status[$key];
			}
		}
		return $return;
	}

	public function setService($name, $action)
	{
		// $action = start | stop | restart | enable | disable
		if (!in_array($action, array('start', 'stop', 'restart', 'enable', 'disable'), true)) {
			return array('success' => false, 'message' => "Invalid action '$action'");
		}

		$service_name = str_replace('.service', '', $name);
		if (!in_array($service_name, $this->getServicesList(), true)) {
			return array('success' => false, 'message' => "Invalid service '$service_name'");
		}

		return $this->pushAction("service-action", [$name, $action]);
	}

	private function pushAction(string $action, array $args, ?string $log = null): array
	{
		if ($this->redis) {
			$payload = json_encode(['run' => $action, 'args' => $args, 'log' => $log]);
			$this->redis->rpush('service-runner', $payload);
			if ($this->log) {
				$this->log->info("ServiceModel::pushAction() service-runner trigger sent for action '$action'");
			}
			return array('success' => true, 'message' => "service-runner trigger sent for action '$action'");
		}

		if ($this->log) {
			$this->log->error("ServiceModel::pushAction() Redis not enabled. Cannot execute action '$action' safely.");
		}
		return array('success' => false, 'message' => 'Redis is required to run service commands');
	}

	public function getServices(): array
	{
		$services = array();
		foreach ($this->getServicesList() as $service) {
			$status = $this->getServiceStatus("$service.service");

			// Skip if empty
			if (empty($status)) {
				$services[$service] = array();
				continue;
			}

			$loadState = isset($status['LoadState']) ? $status['LoadState'] : 'not-found';
			$activeState = isset($status['ActiveState']) ? $status['ActiveState'] : 'inactive';
			$subState = isset($status['SubState']) ? $status['SubState'] : '';

			// Keep service status payload minimal and let the view derive labels/styles.
			$services[$service] = array(
				'loadstate' => ucfirst($loadState),
				'state' => ucfirst($activeState),
				'substate' => ucfirst($subState),
				'unitfilestate' => isset($status['UnitFileState']) ? $status['UnitFileState'] : false,
			);
		}

		// Hide mqtt_input if not found
		if (isset($services['mqtt_input']) && isset($services['mqtt_input']['loadstate']) && $services['mqtt_input']['loadstate'] == 'Not-found') {
			unset($services['mqtt_input']);
		}

		// add custom messages for feedwriter service
		if (isset($services['feedwriter'])) {
			$substate = strtolower(isset($services['feedwriter']['substate']) ? $services['feedwriter']['substate'] : '');
			$state = strtolower(isset($services['feedwriter']['state']) ? $services['feedwriter']['state'] : '');
			if ($state === 'active' && $substate === 'running') {
				$sleep = isset($this->settings['feed']['redisbuffer']['sleep']) ? $this->settings['feed']['redisbuffer']['sleep'] : 5;
				$services['feedwriter']['note'] = 'sleep ' . $sleep . 's';
			}
		}

		return $services;
	}
}
