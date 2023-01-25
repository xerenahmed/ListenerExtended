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

namespace xerenahmed\law;

use pocketmine\event\Listener;
use SOFe\AwaitGenerator\Await;
use function implode;
use function var_dump;

class WrappedListener implements Listener{
	public function __construct(private Listener $listener){}

	public function __call(string $name, mixed $arguments): void{
		$rm = new \ReflectionMethod($this->listener, $name);
		$returnType = $rm->getReturnType();
		var_dump($returnType);

		echo "Calling object method '$name' "
			. implode(', ', $arguments) . "\n";

		Await::g2c(
			$this->listener->{$name}(),
			function() use($name) {
				var_dump($name . ' finished');
			}
		);
	}
}
