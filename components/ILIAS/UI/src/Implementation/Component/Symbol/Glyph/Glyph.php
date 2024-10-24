<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\UI\Implementation\Component\Symbol\Glyph;

use ILIAS\UI\Component as C;
use ILIAS\UI\Component\Counter\Counter;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Implementation\Component\ComponentHelper;
use ILIAS\UI\Implementation\Component\JavaScriptBindable;
use ILIAS\UI\Implementation\Component\Triggerer;

class Glyph implements C\Symbol\Glyph\Glyph
{
    use ComponentHelper;
    use JavaScriptBindable;
    use Triggerer;

    private static array $types = [
        self::SETTINGS,
        self::COLLAPSE,
        self::COLLAPSE_HORIZONTAL,
        self::EXPAND,
        self::ADD,
        self::REMOVE,
        self::UP,
        self::DOWN,
        self::BACK,
        self::NEXT,
        self::SORT_ASCENDING,
        self::SORT_DESCENDING,
        self::USER,
        self::MAIL,
        self::NOTIFICATION,
        self::TAG,
        self::NOTE,
        self::COMMENT,
        self::BRIEFCASE,
        self::LIKE,
        self::LOVE,
        self::DISLIKE,
        self::LAUGH,
        self::ASTOUNDED,
        self::SAD,
        self::ANGRY,
        self::EYEOPEN,
        self::EYECLOSED,
        self::ATTACHMENT,
        self::RESET,
        self::APPLY,
        self::SEARCH,
        self::HELP,
        self::CALENDAR,
        self::TIME,
        self::CLOSE,
        self::MORE,
        self::DISCLOSURE,
        self::LANGUAGE,
        self::LOGIN,
        self::LOGOUT,
        self::BULLETLIST,
        self::NUMBEREDLIST,
        self::LISTINDENT,
        self::LISTOUTDENT,
        self::FILTER,
        self::HEADER,
        self::ITALIC,
        self::BOLD,
        self::LINK,
        self::LAUNCH,
        self::ENLARGE,
        self::LIST_VIEW,
        self::PREVIEW,
        self::SORT,
        self::COLUMN_SELECTION,
        self::TILE_VIEW
    ];

    private string $type;
    private ?string $action;
    private string $label;
    private array $counters;
    private bool $highlighted;
    private bool $active = true;

    public function __construct(string $type, string $label, string $action = null)
    {
        $this->checkArgIsElement("type", $type, self::$types, "glyph type");

        $this->type = $type;
        $this->label = $label;
        // @deprecated with 10 - parameter $action will be removed; use a Button with a Glyph as label
        $this->action = $action;
        $this->counters = array();
        $this->highlighted = false;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function withLabel(string $label): self
    {
        $clone = clone $this;
        $clone->label = $label;
        return $clone;
    }

    /**
     * @deprecated with 10; use a Button with a Glyph as label
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getCounters(): array
    {
        return array_values($this->counters);
    }

    public function withCounter(Counter $counter): C\Symbol\Glyph\Glyph
    {
        $clone = clone $this;
        $clone->counters[$counter->getType()] = $counter;
        return $clone;
    }

    public function isHighlighted(): bool
    {
        return $this->highlighted;
    }

    public function withHighlight(): C\Symbol\Glyph\Glyph
    {
        $clone = clone $this;
        $clone->highlighted = true;
        return $clone;
    }

    /**
     * @deprecated with 10; use a Button with a Glyph as label
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @deprecated with 10; use a Button with a Glyph as label
     */
    public function withUnavailableAction(): C\Symbol\Glyph\Glyph
    {
        $clone = clone $this;
        $clone->active = false;
        return $clone;
    }

    /**
     * @deprecated with 10; use a Button with a Glyph as label
     */
    public function withOnClick(Signal $signal): C\Clickable
    {
        return $this->withTriggeredSignal($signal, 'click');
    }

    /**
     * @deprecated with 10; use a Button with a Glyph as label
     */
    public function appendOnClick(Signal $signal): C\Clickable
    {
        return $this->appendTriggeredSignal($signal, 'click');
    }

    /**
    * @deprecated with 10; use a Button with a Glyph as label
    */
    public function withAction($action): C\Symbol\Glyph\Glyph
    {
        $clone = clone $this;
        $clone->action = $action;
        return $clone;
    }

    /**
     * @deprecated with 10; use a Button with a Glyph as label
     */
    public function isTabbable(): bool
    {
        $has_action = ($this->action !== null && $this->action !== "");
        $has_signal = isset($this->triggered_signals['click']) && $this->triggered_signals['click'] !== null;
        return  ($has_signal || $has_action) && $this->isActive();
    }
}
