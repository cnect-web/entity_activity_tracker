# Entity Activity Tracker
## What is this?
This module gives the ability to create trackers (Config Entity) to track entities on given events and storing an arbitrary value that determines the amount of activity directly or indirectly all depends on trackers are configured.
The module relies on hook_event_dispatcher module to keep track of core events like:
- cron
- entity.insert
- entity.update
- entity.delete

Then an EventSubscriber (ActivitySubscriber) will dispatch our own event via dispatchActivityEvent method (see line 92) and (ActivityEventDispatcher). At this point we attach the tracker, so we have the tracker and all plugin configuration available when processing.

After dispatching our own Event we pass again in ActivitySubscriber where all our own "activity events" will be queued.

The module has 2 Queue Workers (ActivityProcessorQueue and DecayQueue).
The **ActivityProcessorQueue** will receive the event and check if plugin canProcess given event. For complex relationships and indirect activity (group_content) it can happen that a related activity record isn't yet available the time we are processing witch will lead to a reschedule of the event until it is able to process.

This module is dependent of cron to handle decay, and to process queued items.


**This snippet is from ActivitySubscriber and I believe it gives a good overview of what happens with every event:**
```php
/**
 * {@inheritdoc}
 */
public static function getSubscribedEvents() {
    return [
        // System Events.
        HookEventDispatcherInterface::CRON => 'scheduleDecay',
        HookEventDispatcherInterface::ENTITY_INSERT => 'dispatchActivityEvent',
        HookEventDispatcherInterface::ENTITY_UPDATE => 'dispatchActivityEvent',
        HookEventDispatcherInterface::ENTITY_DELETE => 'dispatchActivityEvent',
        // Activity Events.
        ActivityEventInterface::ENTITY_INSERT => 'queueEvent',
        ActivityEventInterface::ENTITY_UPDATE => 'queueEvent',
        ActivityEventInterface::ENTITY_DELETE => 'queueEvent',
        ActivityEventInterface::TRACKER_CREATE => 'queueEvent',
        ActivityEventInterface::TRACKER_DELETE => 'queueEvent',
        ActivityEventInterface::DECAY => 'applyDecay',
    ];
}
```

More documentation to come...

## Contact
email: adrianodias1994@gmail.com



## License

MIT

**Free Software, Hell Yeah!**
