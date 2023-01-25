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

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use SOFe\AwaitStd\AwaitStd;
use function var_dump;

class PlayerListener implements Listener{
	public function onJoin(PlayerJoinEvent $event, AwaitStd $awaitStd, Main $main): \Generator{
		var_dump('rand: ' . $main->getRandomInt());
		var_dump('joined and doing tasks');

		yield from $awaitStd->sleep(20 * 5);

		var_dump('tasks are done, join finished');
	}

	public function onQuit(PlayerQuitEvent $event, Main $main): void{
		var_dump('rand: ' . $main->getRandomInt());
		var_dump('quit');
	}

	public function onChat(PlayerChatEvent $event, Main $main): bool{
		var_dump('rand: ' . $main->getRandomInt());
		if ($event->getMessage() == 'sa') {
			var_dump('should cancel');
			return false;
		}

		return true;
	}

	// @phpstan-ignore-next-line
	public function onLogin(PlayerLoginEvent $event, Main $main){
		var_dump('rand: ' . $main->getRandomInt());
		var_dump('login event, everything is normal');
	}
}
