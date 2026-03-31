<?php

declare(strict_types=1);

namespace aliuly\worldprotect;

use aliuly\worldprotect\common\BasicCli;

abstract class BaseWp extends BasicCli{
	protected $wcfg = [];

	//
	// Config look-up cache
	//
	public function setCfg(string $world, $value) {
		$this->wcfg[$world] = $value;
	}

	public function unsetCfg(string $world) {
		if(isset($this->wcfg[$world])) unset($this->wcfg[$world]);
	}

	public function getCfg(string $world, $default) {
		if(isset($this->wcfg[$world])) return $this->wcfg[$world];
		return $default;
	}
}
