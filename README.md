# ListenerExtended

Let's make PocketMine event listeners powerful!


> **Info**
> 
> An example plugin is in the [example](https://github.com/xerenahmed/ListenerExtended/tree/main/example) folder of root project.

## Table Of Contents
[AwaitGenerator](#AwaitGenerator)  
[Contexts](#Contexts)   
[Cancel On Return False](#cancelOnReturnFalse)

## Quick Look

```php
$awaitStd = AwaitStd::init($this);
ListenerExtended::create()
    ->cancelOnFalse() // if an event handler returns boolean type, cancel it
    ->awaitGenerator() // if an event handler returns Generator type, execute it in AwaitGenerator
    // Contexts
    ->awaitContext($awaitStd) // pass only for await-generator handlers
    ->context($this) // pass to every handler, pass after awaitContext to await-generator handlers
    ->registerEvents($this, new PlayerListener());
```

## Features

### AwaitGenerator
Wrap your generator using events directly!

```php
awaitGenerator(array|\Closure $catchers = []) // if an event handler returns Generator type, execute it in AwaitGenerator. Pass catchers for error handling.
awaitContext(mixed ...$value) // pass only for await-generator handlers
```

If you configure with above API, below event handler will work accurately.
```php
public function onJoin(PlayerJoinEvent $event, AwaitStd $awaitStd): \Generator{
    // start
        
    // wait 5 seconds
    yield from $awaitStd->sleep(20 * 5); // some work

    // do some work
}
```

### Contexts
Pass context or some API directly to event handler parameters.

```php
context(mixed ...$value)
```

Examples:
```php
ListenerExtended::create()
    // wrong
    // ->context($counter = 1) // do not pass value directly, use objects instead
    // correct
    // ->context((new \stdClass)->counter = 1)
    // you can pass more Contexts
    ->context(Server::getInstance(), $someApi, $databaseMaybe)
```

### Cancel On False Return
If a event handler return type is `boolean` and return false, event will cancelled.

```php
ListenerExtended::create()
    ->cancelOnFalse()
//  ^^^^^^^^^^^^^^^^^ enable first
```

Example:
```php
public function onChat(PlayerChatEvent $event, Main $main): bool{
    if ($main->isChatDisabled()) {
        return false; // will cancel event automaticly
    }

    return true; // do nothing
}
```


#### Do you have any idea?
Open issue please!

#### Digital Ocean
Get 200$ credit on the best cloud service!

[![DigitalOcean Referral Badge](https://web-platforms.sfo2.digitaloceanspaces.com/WWW/Badge%203.svg)](https://www.digitalocean.com/?refcode=68d7bc7aff41&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge)

#### Another Projects You Might Like
- [DatabaseExecutor](https://github.com/xerenahmed/DatabaseExecutor) is a virion library for executing SQL queries asynchronously with Laravel models and builders.

