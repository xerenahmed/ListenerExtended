<?php

/*
 * ______         _ __  ___ ____
 * | ___ \       | |  \/  /  __ \
 * | |_/ /___  __| | .  . | /  \/
 * |    // _ \/ _` | |\/| | |
 * | |\ \  __/ (_| | |  | | \__/\
 * \_| \_\___|\__,_\_|  |_/\____/
 *
 * Copyright (C) RedMC Network, Inc - All Rights Reserved
 *
 * You may use, distribute and modify this code under the
 * terms of the MIT license, which unfortunately won't be
 * written for another century.
 *
 * Written by xerenahmed <eren@redmc.me>, 2023
 *
 * @author RedMC Team
 * @link https://www.redmc.me/
 */

declare(strict_types=1);

namespace xerenahmed\example;

use pocketmine\plugin\PluginBase;
use SOFe\AwaitStd\AwaitStd;
use xerenahmed\ListenerExtended\ListenerExtended;
use function rand;

class Main extends PluginBase{

	public function onEnable(): void{
		$awaitStd = AwaitStd::init($this);
		ListenerExtended::create()
			->cancelOnFalse()
			->awaitGenerator()
			->awaitContext($awaitStd)
			->context($this)
			->registerEvents($this, new PlayerListener());
	}

	public function getRandomInt(): int{
		return rand();
	}
}
