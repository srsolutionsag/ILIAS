# Learning History Service

Welcome to the history of learning.

If your component wants to add entries to the learning history it must:

1. Add an entry in the `$provider` array under `components/ILIAS/LearningHistory/classes/class.ilLearningHistoryProviderFactory.php`. It is planned to switch this to a future service discovery concept.
2. The class provided at this location must extend `components/ILIAS/LearningHistory/interfaces/class.ilAbstractLearningHistoryProvider.php` and implement `components/ILIAS/LearningHistory/interfaces/interface.ilLearningHistoryProvider.php`.

Method `getEntries($ts_start, $ts_end)` must return all learning history entries between the unix timestamps `$ts_start` and `$ts_end`. To create the entries in the `ilLearningHistoryEntry[]` array a factory provided by the service should be used.

```
$entries[] = $this->getFactory()->entry($text1, $text2,
	ilUtil::getImagePath("my_icon.svg"),
	$ts,
	$obj_id, $ref_id);
```

The learning history service will try to determine a parent course using the optional `$ref_id` parameter. If this is not possible or provided `$text1` will be used in the presentation of the entry, otherwise `$text2`. In `$text1` a placeholder `$1$` can be used that will be replace with the object title of object with ID `$obj_id`. In `$text2` `$1$` and an additional placeholder `$2$` can be used. `$2$` will be replaced with the course title of the parent course.

E.g. the learning progress uses these language variables:
```
trac#:#trac_lhist_obj_completed#:#$1$ was completed.
trac#:#trac_lhist_obj_completed_in#:#$1$ was completed in $2$.
```

If you additionally want to emphasize certain words (mostly titles) in your text, please use the method `$this->getEmphasizedTitle($string)`.

## Deletion Behaviour

The service will keep entries even if the referenced objects ($ref_id, $obj_id) are deleted in the meantime. However the consuming components must take care of their own deletion processes and historise data that is necessary to provide older entries for the learning history. [^2]

[^1] 8 Oct 2018, General introduction of the service: https://github.com/ILIAS-eLearning/ILIAS/pull/1210

[^2] 18 Sep 2023, Clarification Deletion Behaviour, 4.5: https://docu.ilias.de/goto_docu_wiki_wpage_8022_1357.html