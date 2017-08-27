# IllchukLock

[![Build Status](https://travis-ci.org/BeauCal/beaucal-long-throttle.svg?branch=master)](https://travis-ci.org/BeauCal/beaucal-long-throttle)

**Now with 100% code coverage.**

Prevent an action for some amount of time.  Hours, day, months, years, anything.
Allows for multiple locks (e.g. 100/day) and clearing/releasing a lock just made.
And it works just like it should, every single lock lasts exactly how long you
specify and is taken atomically.

### Installation
1. In `application.config.php`, add as follows:

```PHP
'modules' => [..., 'IllchukLock', ...];
```

2. Import into your database `data/illchuk_lock.sql`:
```SQL
CREATE TABLE IF NOT EXISTS `illchuk_lock` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `key` varchar(255) NOT NULL UNIQUE KEY,
  `end_datetime` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `illchuk_lock` ADD INDEX (`end_datetime`);
```


### To Use

Either you get the lock or you don't.

```PHP
// in controller
$locker = $this->getServiceLocator()->get('IllchukLock');

// term?
$term = new DateTimeUnit(2, 'weeks'); // or
$term = new DateTimeEnd(new DateTime('+2 weeks'));

if ($locker->takeLock('BiWeeklyReport', $term)) {
    // lock is taken atomically, made for 2 weeks: safe to do your work
}
else {
    // locked from before: leave it alone & perhaps try again later
}

/**
 * N.B. May throw \IllchukLock\Exception\PhantomLockException, when
 * lock is reported to be set but upon verification step is actually not.
 * This is truly exceptional and shouldn't be just thrown aside.
 */
```


### Allow Multiple Locks
You can allow any number of locks e.g. 'lock1' => 5/hour, 'lock2' => 100/day.  Here's how:

```PHP
// copy IllchukLock.global.php to your config/autoload/
$locker = [
// ...
    'adapter_class' => 'IllchukLock\Adapter\DbMultiple', // was Adapter\Db
// ...
]
$regexCounts = [
    /**
     * E.g. You can create 3 'do-stuff' locks before the lock can't be taken.
     * Those not matching here are allowed the usual 1.
     */
    '/^do-stuff$/' => 3
];

// in controller
$locker = $this->getServiceLocator()->get('IllchukLock');
$locker->takeLock('do-stuff', new DateTimeUnit(1, 'day')); // YES
$locker->takeLock('do-stuff', new DateTimeUnit(1, 'day')); // YES
$locker->takeLock('do-stuff', new DateTimeUnit(1, 'day')); // YES
$locker->takeLock('do-stuff', new DateTimeUnit(1, 'day')); // FALSE
// ...
// A DAY LATER
$locker->takeLock('do-stuff', new DateTimeUnit(1, 'day')); // YES
```

### Clearing Locks

```PHP
$locker = $this->getServiceLocator()->get('IllchukLock');
$handle = $locker->takeLock('year-end', new DateTimeUnit(1, 'year')); // YES
$locker->takeLock('year-end', new DateTimeUnit(1, 'year')); // FALSE
if ($whoopsBackingOut) {
    $locker->clearLock($handle);
}
$locker->takeLock('year-end', new DateTimeUnit(1, 'year')); // YES
```


### Lock with APC

For something more quick-n-dirty, use APC locking. This is adequate
for short-term throttling with the usual caveats regarding APC persistence
(e.g. some other part of your app might flush the entire cache, a PHP restart, out of memory).

N.B. If `takeLock()` fails, don't try to `sleep()` it out;
that won't work for some reason to do with how `apc_add()` works.
Instead, handle the no-lock condition then try again next request.

```PHP

// copy illchuklock.global.php to your config/autoload/
$locker = [
// ...
    'adapter_class' => 'IllchukLock\Adapter\Apc', // was Adapter\Db
// ...
]

// from service manager
$locker = $container->get('IllchukLock');


// alternatively, a shortcut factory that doesn't require config
$locker = $container->get('illchuk_lock_apc');

```
