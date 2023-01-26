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

namespace xerenahmed\ListenerExtended;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\ListenerMethodTags;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;
use SOFe\AwaitGenerator\Await;
use function array_map;
use function count;
use function get_class;
use function implode;
use function is_a;
use function method_exists;
use function sprintf;
use function strtolower;

class ListenerExtended{
	private bool $cancelOnReturnFalse = false;
	private bool $wrapWithAwaitGenerator = false;
	/** @phpstan-var array<string, callable(\Throwable): void>|callable(\Throwable): void $awaitGeneratorCatcher */
	private $awaitGeneratorCatcher = [];

	/** @var mixed[] $context */
	private array $context = [];
	/** @var mixed[] $awaitContext */
	private array $awaitContext = [];

	public static function create(): self {
		return new self();
	}

	/** @phpstan-param array<string, callable(\Throwable): void>|callable(\Throwable): void $awaitGeneratorCatcher */
	public function awaitGenerator($awaitGeneratorCatcher = []): self{
		$this->wrapWithAwaitGenerator = true;
		$this->awaitGeneratorCatcher = $awaitGeneratorCatcher;
		return $this;
	}

	public function cancelOnFalse(): self{
		$this->cancelOnReturnFalse = true;
		return $this;
	}

	public function context(mixed ...$var): self{
		$this->context = $var;
		return $this;
	}

	public function awaitContext(mixed ...$var): self{
		$this->awaitContext = $var;
		return $this;
	}

	public function registerEvents(PluginBase $plugin, Listener ...$listeners): void{
		if(!$plugin->isEnabled()){
			throw new PluginException("Plugin attempted to register " . implode(',', array_map(fn($c) => get_class($c), $listeners)) . " while not enabled");
		}

		foreach ($listeners as $listener) {
			$this->registerEventsFor($plugin, $listener);
		}
	}

	private function registerEventsFor(PluginBase $plugin, Listener $listener): void{
		$manager = $plugin->getServer()->getPluginManager();

		$reflection = new \ReflectionClass(get_class($listener));
		foreach($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
			$tags = Utils::parseDocComment((string) $method->getDocComment());
			if(isset($tags[ListenerMethodTags::NOT_HANDLER]) || ($eventClass = $this->getEventsHandledBy($method)) === null){
				continue;
			}
			$handlerClosure = $method->getClosure($listener);
			if($handlerClosure === null) throw new AssumptionFailedError("This should never happen");

			try{
				$priority = isset($tags[ListenerMethodTags::PRIORITY]) ? EventPriority::fromString($tags[ListenerMethodTags::PRIORITY]) : EventPriority::NORMAL;
			}catch(\InvalidArgumentException $e){
				throw new PluginException("Event handler " . Utils::getNiceClosureName($handlerClosure) . "() declares invalid/unknown priority \"" . $tags[ListenerMethodTags::PRIORITY] . "\"");
			}

			$handleCancelled = false;
			if(isset($tags[ListenerMethodTags::HANDLE_CANCELLED])){
				if(!is_a($eventClass, Cancellable::class, true)){
					throw new PluginException(sprintf(
						"Event handler %s() declares @%s for non-cancellable event of type %s",
						Utils::getNiceClosureName($handlerClosure),
						ListenerMethodTags::HANDLE_CANCELLED,
						$eventClass
					));
				}
				switch(strtolower($tags[ListenerMethodTags::HANDLE_CANCELLED])){
					case "true":
					case "":
						$handleCancelled = true;
						break;
					case "false":
						break;
					default:
						throw new PluginException("Event handler " . Utils::getNiceClosureName($handlerClosure) . "() declares invalid @" . ListenerMethodTags::HANDLE_CANCELLED . " value \"" . $tags[ListenerMethodTags::HANDLE_CANCELLED] . "\"");
				}
			}

			$returnType = $method->getReturnType()?->__toString();
			if ($returnType !== null) {
				$returnType = strtolower($returnType);
			}
			$modified = false;
			if ($returnType === 'bool' && $this->cancelOnReturnFalse) {
				if(!is_a($eventClass, Cancellable::class, true)){
					throw new PluginException(sprintf(
						"Event handler %s() returns boolean for non-cancellable event of type %s",
						Utils::getNiceClosureName($handlerClosure),
						$eventClass
					));
				}

				if (!method_exists($eventClass, 'cancel')) {
					throw new PluginException(sprintf(
						"Event handler %s() returns boolean but the event %s has no cancel method",
						Utils::getNiceClosureName($handlerClosure),
						$eventClass
					));
				}

				$handlerClosure = function($event) use($handlerClosure) {
					$ret = $handlerClosure($event, ...$this->context);
					if (!$ret) {
						$event->cancel();
					}
					return $ret;
				};
				$modified = true;
			}

			if ($returnType === 'generator' && $this->wrapWithAwaitGenerator) {
				$handlerClosure = function($event) use ($handlerClosure) {
					$handle = $handlerClosure($event, ...$this->awaitContext, ...$this->context);
					Await::g2c($handle, null, $this->awaitGeneratorCatcher);
				};
				$modified = true;
			}

			if (!$modified && count($this->context) > 0) {
				$handlerClosure = fn($event) => $handlerClosure($event, ...$this->context);
			}

			$manager->registerEvent($eventClass, $handlerClosure, $priority, $plugin, $handleCancelled);
		}
	}

	/** @return ?class-string<Event> */
	private function getEventsHandledBy(\ReflectionMethod $method): ?string{
		if($method->isStatic() || !$method->getDeclaringClass()->implementsInterface(Listener::class)){
			return null;
		}
		$tags = Utils::parseDocComment((string) $method->getDocComment());
		if(isset($tags[ListenerMethodTags::NOT_HANDLER])){
			return null;
		}

		$parameters = $method->getParameters();
		if(count($parameters) < 1){
			return null;
		}

		$paramType = $parameters[0]->getType();
		//isBuiltin() returns false for builtin classes ..................
		if(!$paramType instanceof \ReflectionNamedType || $paramType->isBuiltin()){
			return null;
		}

		/** @phpstan-var class-string $paramClass */
		$paramClass = $paramType->getName();
		$eventClass = new \ReflectionClass($paramClass);
		if(!$eventClass->isSubclassOf(Event::class)){
			return null;
		}

		/** @var \ReflectionClass<Event> $eventClass */
		return $eventClass->getName();
	}
}
