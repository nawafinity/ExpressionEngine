<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Validation\Rule;

use ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Alphabetical, Dashes, Periods, and Emoji Validation Rule
 */
class AlphaDashPeriodEmoji extends ValidationRule
{
    public function validate($key, $value)
    {
        $emojiless = $this->stripEmojis($value);

        // If the only value we were given were emoji(s) then it's valid
        if (strlen($value) > 0 && strlen($emojiless) < 1) {
            return true;
        }

        // Support Arabic characters
        return (bool) preg_match("/^([-\p{Arabic}\-\p{Ll}0-9_.-])+$/ui", $emojiless);
    }

    protected function stripEmojis($value)
    {
        $regex = '/(?:' . EMOJI_REGEX . ')/u';

        $value = preg_replace($regex, '', (string) $value);

        return $value;
    }

    public function getLanguageKey()
    {
        return 'alpha_dash_period';
    }
}

// EOF
