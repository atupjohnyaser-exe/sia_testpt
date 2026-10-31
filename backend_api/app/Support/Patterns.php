<?php

namespace App\Support;

/**
 * Character whitelists for the fields where a short, predictable format makes sense.
 * Whitelists are safer than trying to list every bad character.
 */
final class Patterns
{
    /** Letters (any language), spaces, periods, apostrophes, hyphens. No digits. */
    public const NAME = "/^[\\pL\\pM][\\pL\\pM .'’-]*$/u";

    /** Like a name, but digits and quotes are allowed, for street names like "Tin Man" or 'Mr. "K"'. */
    public const ALIAS = "/^[\\pL\\pM\\pN][\\pL\\pM\\pN .'’\"“”-]*$/u";

    /** Eye and hair colour: letters, spaces, slash, hyphen. */
    public const COLOR = "/^\\pL[\\pL \\/-]*$/u";

    /** Heights like 5'8", 5 ft 8 in, 173 cm. */
    public const HEIGHT = "/^[0-9'\"’”′″. cmftin-]{1,20}$/iu";

    /** Badge ids like RP-1047. */
    public const BADGE = '/^[A-Za-z0-9-]{3,20}$/';
}
