<?php
/**
 * Quietroom: crisis detection and support resources
 *
 * This module is intentionally conservative: it errs towards showing a human
 * resource rather than staying silent. It never replaces professional care.
 */

class Crisis
{
    public const LEVEL_NONE     = 'none';
    public const LEVEL_ELEVATED = 'elevated';
    public const LEVEL_HIGH     = 'high';
    public const LEVEL_CRITICAL = 'critical';

    /**
     * Ordered most severe first.
     *
     * The intent patterns deliberately require a life-directed object
     * ("kill myself", "end my life") instead of a bare verb, so that everyday
     * phrases such as "I want to kill time" or "I am going to kill it at
     * work" are not treated as a crisis.
     */
    private const PATTERNS = [
        self::LEVEL_CRITICAL => [
            'suicide_intent' => '/
                (?:
                    \b(?:kill|killing|end|ending|take|taking)\s+(?:myself|my\s?self|my\s+own\s+life|my\s+own\s+soul|my\s+life|my\s+existence)\b
                  | \b(?:i|we)\s*(?:\'m|am|m)?\b\s*(?:going\s+to|gonna|about\s+to|will|want\s+to|wanna|planning\s+to|plan\s+to|have\s+to|need\s+to|decided\s+to|decide\s+to|must|can|could|may|might|would)?\s*(?:kill|end|take)(?!\s+(?:time|it|that|this|them|him|her|my|the|a|an|up)\b)\b
                  | \b(?:i|we)\s*(?:\'m|am|m)?\b\s*(?:going\s+to|gonna|about\s+to|will|want\s+to|wanna|planning\s+to|plan\s+to|have\s+to|need\s+to|decided\s+to|decide\s+to|must|can|could|may|might|would)?\s*(?:end|ending)\s+it\b
                  | \bcommit\s+suicide\b
                  | \bsuicid\w*\s+(?:tonight|today|tomorrow|now|this\s+week)\b
                  | \b(?:take|overdose|swallow)\s+(?:all|too\s+many|my)\s+pills?\b
                  | \bwant\s+to\s+die\b
                  | \bbetter\s+off\s+dead\b
                  | \b(?:do\s+not|don\'?t|doesn\'?t)\s+want\s+to\s+(?:be\s+alive|live)\b
                  | \bdon\'?t\s+want\s+to\s+be\s+alive\b
                  | \bwish\s+i\s+was\s+dead\b
                  | \bended\s+my\s+life\b
                  | \bnot\s+be\s+here\s+tomorrow\b
                  | \bno\s+point\s+going\s+on\b
                  | \bgive\s+up\s+on\s+life\b
                  | \bkill\s+myself\b
                  | \bhurt\s+myself\s+and\s+not\s+feel\s+it\b
                  | \bplanned\s+to\s+end\s+it\b
                )
            /xu',
            'harm_others'    => '/\b(?:kill|hurt|harm|shoot|stab|attack)\s+(?:them|him|her|people|someone|my\s+(?:family|mum|mom|dad|brother|sister|children))\b|\b(?:mass|school)\s+shooting\b|\bi\s+want\s+to\s+hurt\s+(?:people|someone|them)\b|\bright\s+in\s+front\s+of\s+(?:them|everyone)\b|\bplan\s+to\s+kill\b/u',
            'ending_it'      => '/
                (?:
                    \b(?:i|we)\s*(?:\'m|am|m)?\b\s*(?:going\s+to|gonna|about\s+to|will|want\s+to|wanna|planning\s+to|plan\s+to|have\s+to|need\s+to|decided\s+to|decide\s+to|must|can|could|may|might|would)?\s*(?:end|ending)\s+it\b
                  | \b(?:end|ending)\s+my\s+life\b
                  | \bnot\s+going\s+to\s+be\s+around\b
                  | \bwon\'?t\s+be\s+(?:here|around)\s+(?:tomorrow|anymore)\b
                )
            /xu',
        ],
        self::LEVEL_HIGH => [
            'self_harm'      => '/\b(?:cut(?:ting|ting)?\s+(?:my ?self|myself)|self[\s\-]?harm(?:ing)?|burn(?:ing)?\s+my\s?self|hurt(?:ing)?\s+(?:my\s?self|myself)|overdos\w+|starv(?:e|ing)\s+myself|scratch(?:ing)?\s+my ?self)\b/u',
            'hopeless'      => '/\b(?:hopeless|hopelessly|pointless|no\s+reason\s+to\s+(?:live|go\s+on)|can\'?t\s+go\s+on|give\s+up\s+(?:on\s+everything|completely)|nothing\s+(?:matters|will\s+ever\s+get\s+better)|trapped|unbearable|can\'?t\s+take\s+(?:it|this|anymore)|nobody\s+(?:would|wouldn\'?t)\s+(?:care|miss|notice))\b/u',
            'suicidal_talk' => '/\b(?:suicid\w*|self[\s\-]?harm\w*|kill\s+myself|end\s+my\s+life|take\s+my\s+own\s+life)\b/u',
            'not_safe'      => '/\b(?:i\s*\'?m\s+not\s+safe|i\s+am\s+not\s+safe|i\s+feel\s+unsafe|i\s+don\'?t\s+feel\s+safe|can\'?t\s+keep\s+myself\s+safe|afraid\s+i\'?ll\s+do\s+something)\b/u',
        ],
        self::LEVEL_ELEVATED => [
            'overwhelmed'   => '/\b(?:can\'?t\s+(?:take|handle|cope|do)\s+(?:this|it|any\s?more|anymore)|falling\s+apart|breaking\s+down|overwhelmed|overwhelming|running\s+out\s+of\s+road)\b/u',
            'numb'          => '/\b(?:completely\s+numb|feeling\s+numb|nothing\s+at\s+all|emotionally\s+numb|don\'?t\s+feel\s+anything)\b/u',
            'panic'         => '/\b(?:panic\s+attack|having\s+a\s+panic\s+attack|can\'?t\s+breathe|hyperventilat\w+|heart\s+racing\s+out\s+of\s+control)\b/u',
        ],
    ];

    /** Extra obfuscation-resistant checks on normalised text. */
    private const EXTRA = [
        self::LEVEL_CRITICAL => [
            'spaced_out' => '/(?:k\s*i\s*l\s*l\s*m\s*y\s*s\s*e\s*l\s*f|c\s*o\s*m\s*m\s*i\s*t\s+s\s*u\s*i\s*c\s*i\s*d\s*e)/u',
        ],
    ];

    public static function normalise(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;
        $text = preg_replace('/[^\p{L}\p{N}\s\'\-\?!.,]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return $text;
    }

    /**
     * @return array{level:string,categories:array<int,string>,matches:array<int,string>}
     */
    public static function assess(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['level' => self::LEVEL_NONE, 'categories' => [], 'matches' => []];
        }
        $normalised = self::normalise($text);

        foreach ([self::LEVEL_CRITICAL, self::LEVEL_HIGH, self::LEVEL_ELEVATED] as $level) {
            $categories = [];
            $matches = [];
            foreach (self::PATTERNS[$level] as $name => $pattern) {
                if (preg_match($pattern, $normalised)) {
                    $categories[] = $name;
                    $matches[] = $name;
                }
            }
            foreach (self::EXTRA[$level] ?? [] as $name => $pattern) {
                if (preg_match($pattern, $normalised)) {
                    $categories[] = $name;
                    $matches[] = $name;
                }
            }
            if ($categories) {
                return ['level' => $level, 'categories' => $categories, 'matches' => $matches];
            }
        }

        return ['level' => self::LEVEL_NONE, 'categories' => [], 'matches' => []];
    }

    public static function levelRank(string $level): int
    {
        return [
            self::LEVEL_NONE     => 0,
            self::LEVEL_ELEVATED => 1,
            self::LEVEL_HIGH     => 2,
            self::LEVEL_CRITICAL => 3,
        ][$level] ?? 0;
    }

    /** @return array<int,array<string,mixed>> */
    public static function resources(?string $country = null): array
    {
        $resources = self::globalResources();
        $country = strtoupper((string) $country);
        if ($country !== '' && isset(self::BY_COUNTRY[$country])) {
            $resources = array_merge($resources, self::BY_COUNTRY[$country]);
        }
        return $resources;
    }

    public static function countryName(?string $code): string
    {
        $code = strtoupper((string) $code);
        return self::COUNTRY_NAMES[$code] ?? 'your country';
    }

    /**
     * The emergency number for a country.
     *
     * The most important button on the crisis page cannot assume 911.
     * That number does not exist in most of the world, and in the UK the
     * right numbers are 999 and 112. The first entry is the one that
     * answers a call about immediate danger; the rest are alternates.
     *
     * @return array{numbers: array<int,string>, label: string}
     */
    public static function emergency(?string $country = null): array
    {
        $code = strtoupper((string) $country);

        $map = [
            'US' => ['911'],
            'CA' => ['911'],
            'GB' => ['999', '112'],
            'IE' => ['999', '112'],
            'AU' => ['000', '112'],
            'NZ' => ['111'],
            'IN' => ['112', '108'],
            'DE' => ['112', '110'],
            'FR' => ['112', '15'],
            'ES' => ['112', '088'],
            'IT' => ['112', '113'],
            'BR' => ['190', '192'],
            'MX' => ['911'],
            'PH' => ['911'],
            'ZA' => ['10111', '112'],
            'NL' => ['112'],
            'SE' => ['112'],
            'CH' => ['112'],
            'NO' => ['112'],
            'DK' => ['112'],
            'FI' => ['112'],
            'PL' => ['112'],
            'PT' => ['112'],
            'GR' => ['112', '100'],
            'TR' => ['112'],
            'AE' => ['999', '112'],
            'SA' => ['999', '997'],
            'KE' => ['999', '112'],
            'NG' => ['112'],
            'AR' => ['911', '107'],
            'CL' => ['133', '131'],
            'CO' => ['123', '132'],
        ];

        $numbers = $map[$code] ?? ['911'];
        $label  = count($numbers) > 1
            ? implode(' or ', $numbers)
            : $numbers[0];

        return ['numbers' => $numbers, 'label' => $label];
    }

    /**
     * The tap-to-call and tap-to-text links for a helpline, if it has them.
     *
     * The action text already carries the number ("Call 116 123", "Text HOME
     * to 741741"), so the dialling links are derived from it instead of being
     * typed out a second time for every country. Someone in trouble should be
     * able to put a finger on the number, not hunt for it on a web page.
     *
     * @param  array<string,mixed> $resource
     * @return array<int,array{href:string,label:string}>
     */
    public static function dialLinks(array $resource): array
    {
        $action = trim((string) ($resource['action'] ?? ''));
        $links  = [];

        if (preg_match('/^Text\s+(?:([A-Za-z]+)\s+to\s+)?([\d][\d\s\-\.]*)/i', $action, $m)) {
            $number = preg_replace('/\D/', '', $m[2]) ?? '';
            if ($number !== '') {
                $keyword = $m[1] ?? '';
                $links[] = [
                    'href'  => 'sms:' . $number . ($keyword !== '' ? '?body=' . rawurlencode($keyword) : ''),
                    'label' => $action,
                    'kind'  => 'sms',
                ];
            }
        }

        if (preg_match('/^Call\b(.*)$/i', $action, $m) && preg_match('/\d[\d\s\-\.]*/', $m[1], $n)) {
            $number = preg_replace('/\D/', '', $n[0]) ?? '';
            if ($number !== '') {
                $links[] = ['href' => 'tel:' . $number, 'label' => $action, 'kind' => 'tel'];

                // "Call or text 988": the same shortcode answers texts too.
                if (stripos($action, 'or text') !== false) {
                    $links[] = [
                        'href'  => 'sms:' . $number,
                        'label' => 'Text ' . $number,
                        'kind'  => 'sms',
                    ];
                }
            }
        }

        return $links;
    }

    /** @return array<int,array<string,mixed>> */
    private static function globalResources(): array
    {
        return [
            [
                'name'     => 'Find A Helpline',
                'note'     => 'Verified crisis lines in over 130 countries, free and confidential.',
                'action'   => 'findahelpline.com',
                'url'      => 'https://findahelpline.com/',
                'primary'  => true,
            ],
            [
                'name'     => 'Befrienders Worldwide',
                'note'     => 'Emotional support centres in 32+ languages.',
                'action'   => 'befrienders.org',
                'url'      => 'https://www.befrienders.org/',
                'primary'  => false,
            ],
        ];
    }

    private const BY_COUNTRY = [
        'US' => [
            ['name' => '988 Suicide & Crisis Lifeline', 'note' => 'Call or text 988, 24/7, free & confidential.', 'action' => 'Call or text 988', 'url' => 'https://988lifeline.org/', 'primary' => true],
            ['name' => 'Crisis Text Line', 'note' => 'Text HOME to 741741. A crisis counsellor replies by text.', 'action' => 'Text HOME to 741741', 'url' => 'https://www.crisistextline.org/', 'primary' => false],
            ['name' => 'Emergency services', 'note' => 'If you are in immediate danger, call emergency services.', 'action' => 'Call 911', 'url' => 'https://911.gov/', 'primary' => false],
        ],
        'CA' => [
            ['name' => '9-8-8 Suicide Crisis Helpline', 'note' => 'Call or text 9-8-8, 24/7, in English & French.', 'action' => 'Call or text 9-8-8', 'url' => 'https://988.ca/', 'primary' => true],
            ['name' => 'Crisis Text Line', 'note' => 'Text HOME to 741741. A crisis counsellor replies by text.', 'action' => 'Text HOME to 741741', 'url' => 'https://www.crisistextline.org/', 'primary' => false],
            ['name' => 'Emergency services', 'note' => 'If you are in immediate danger, call 911.', 'action' => 'Call 911', 'url' => 'https://www.canada.ca/en/public-health/services/health-promotion/mental-health/mental-health-crisis-lines.html', 'primary' => false],
        ],
        'GB' => [
            ['name' => 'Samaritans', 'note' => 'Free, 24/7, confidential support for anyone.', 'action' => 'Call 116 123', 'url' => 'https://www.samaritans.org/', 'primary' => true],
            ['name' => 'NHS 111', 'note' => 'Urgent mental health help, 24/7.', 'action' => 'Call 111', 'url' => 'https://111.nhs.uk/', 'primary' => false],
            ['name' => 'Shout', 'note' => 'Free text-based support, 24/7.', 'action' => 'Text SHOUT to 85258', 'url' => 'https://giveusashout.org/', 'primary' => false],
        ],
        'IE' => [
            ['name' => 'Samaritans Ireland', 'note' => 'Free, 24/7, confidential.', 'action' => 'Call 116 123', 'url' => 'https://www.samaritans.ie/', 'primary' => true],
            ['name' => 'Emergency services', 'note' => 'If you are in immediate danger, call 999 or 112.', 'action' => 'Call 999 / 112', 'url' => 'https://www.gov.ie/en/service/emergency-services/', 'primary' => false],
        ],
        'AU' => [
            ['name' => 'Lifeline', 'note' => '24/7 crisis support and counselling.', 'action' => 'Call 13 11 14', 'url' => 'https://www.lifeline.org.au/', 'primary' => true],
            ['name' => 'Suicide Call Back Service', 'note' => 'Free professional support, 24/7.', 'action' => 'Call 1300 659 467', 'url' => 'https://www.suicidecallbackservice.org.au/', 'primary' => false],
            ['name' => 'Beyond Blue', 'note' => 'Support line, 24/7.', 'action' => 'Call 1300 22 4636', 'url' => 'https://www.beyondblue.org.au/', 'primary' => false],
        ],
        'NZ' => [
            ['name' => '1737, need to talk?', 'note' => 'Free, 24/7 support from a counsellor.', 'action' => 'Call or text 1737', 'url' => 'https://www.1737.org.nz/', 'primary' => true],
            ['name' => 'Lifeline NZ', 'note' => 'Free, 24/7.', 'action' => 'Call 0800 543 135', 'url' => 'https://www.lifeline.org.nz/', 'primary' => false],
        ],
        'IN' => [
            ['name' => 'iCall Helpline', 'note' => 'Mon–Sat 8am–10pm IST, free counselling.', 'action' => 'Call 9152987821', 'url' => 'https://www.icallhelpline.org/', 'primary' => true],
            ['name' => 'Vandrevala Foundation', 'note' => 'Free, 24/7, multi-language.', 'action' => 'Call 1860-2662-345', 'url' => 'https://www.vandrevala.org/', 'primary' => false],
            ['name' => 'AASRA', 'note' => '24/7 free helpline.', 'action' => 'Call 1800-233-3330', 'url' => 'https://www.aasra.info/', 'primary' => false],
        ],
        'DE' => [
            ['name' => 'Telefonseelsorge', 'note' => 'Free, 24/7, anonymous.', 'action' => 'Call 0800 111 0 111', 'url' => 'https://www.telefonseelsorge.de/', 'primary' => true],
            ['name' => 'Notfallruf 116 117', 'note' => 'Medical on-call service.', 'action' => 'Call 116 117', 'url' => 'https://www.116117.de/', 'primary' => false],
        ],
        'FR' => [
            ['name' => 'Numéro national de prévention du suicide', 'note' => 'Free, 24/7, confidential.', 'action' => 'Call 3114', 'url' => 'https://3114.fr/', 'primary' => true],
            ['name' => 'Santé publique France', 'note' => 'Mental health support and crisis guidance.', 'action' => 'psycom.sante.gouv.fr', 'url' => 'https://psycom.sante.gouv.fr/', 'primary' => false],
        ],
        'ES' => [
            ['name' => 'Línea de atención a la conducta suicida', 'note' => 'Free, 24/7, in Spanish.', 'action' => 'Call 024', 'url' => 'https://www.saludmentalynpsicosocial.gob.es/', 'primary' => true],
            ['name' => 'Teléfono de la Esperanza', 'note' => 'Free, 24/7.', 'action' => 'Call 717 003 717', 'url' => 'https://telefonoesperanza.org/', 'primary' => false],
        ],
        'IT' => [
            ['name' => 'Telefono Amico', 'note' => 'Free, 24/7, confidential.', 'action' => 'Call 02 2327 2327', 'url' => 'https://www.telefonoamico.it/', 'primary' => true],
            ['name' => 'S Telefono Amico', 'note' => 'Free, 24/7, text support.', 'action' => 'Text 02 2327 2327', 'url' => 'https://www.telefonoamico.it/', 'primary' => false],
        ],
        'BR' => [
            ['name' => 'CVV (Centro de Valorização da Vida)', 'note' => 'Free, 24/7, chat/phone/email.', 'action' => 'Call 188', 'url' => 'https://www.cvv.org.br/', 'primary' => true],
        ],
        'MX' => [
            ['name' => 'Línea de la Vida', 'note' => 'Free, 24/7.', 'action' => 'Call 800 911 2000', 'url' => 'https://lineadelavida.org/', 'primary' => true],
        ],
        'PH' => [
            ['name' => 'In Touch Community Services', 'note' => 'Free, 24/7 crisis hotline.', 'action' => 'Call 1553 (landline)', 'url' => 'https://intouchcommunityservices.org/', 'primary' => true],
            ['name' => 'National Mental Health Crisis Hotline', 'note' => 'Free, 24/7.', 'action' => 'Call 1553', 'url' => 'https://www.doh.gov.ph/', 'primary' => false],
        ],
        'ZA' => [
            ['name' => 'SADAG Helpline', 'note' => 'Free, 24/7, 11 languages.', 'action' => 'Call 0800 567 567', 'url' => 'https://www.sadag.org/', 'primary' => true],
        ],
        'NL' => [
            ['name' => '113 Zelfmoordpreventie', 'note' => 'Free, 24/7, confidential.', 'action' => 'Call 0800-0113', 'url' => 'https://www.113.nl/', 'primary' => true],
        ],
        'SE' => [
            ['name' => 'Mind Självmordslinjen', 'note' => 'Free, 24/7.', 'action' => 'Call 90101', 'url' => 'https://mind.se/', 'primary' => true],
        ],
        'CH' => [
            ['name' => 'Die Dargebotene Hand', 'note' => 'Free, 24/7.', 'action' => 'Call 143', 'url' => 'https://www.dargebotenehand.ch/', 'primary' => true],
        ],
        'NO' => [
            ['name' => 'Mental Helse hjelpetelefon', 'note' => 'Free, 24/7.', 'action' => 'Call 116 123', 'url' => 'https://www.mhh.no/', 'primary' => true],
        ],
        'DK' => [
            ['name' => 'Lifeline Danmark', 'note' => 'Free, 24/7.', 'action' => 'Call 70 25 72 72', 'url' => 'https://www.lifeline.dk/', 'primary' => true],
        ],
        'FI' => [
            ['name' => 'Mielenterveys CRIS', 'note' => 'Free, 24/7.', 'action' => 'Call 0800 400 333', 'url' => 'https://mielenterveysseura.fi/', 'primary' => true],
        ],
        'PL' => [
            ['name' => 'Telefon zaufania', 'note' => 'Free, 24/7.', 'action' => 'Call 116 123', 'url' => 'https://telefonzaufania.pl/', 'primary' => true],
        ],
        'PT' => [
            ['name' => 'SNS 24', 'note' => 'Free, 24/7.', 'action' => 'Call 808 24 24 24', 'url' => 'https://www.sns24.gov.pt/', 'primary' => true],
        ],
        'GR' => [
            ['name' => 'A.G.I.M.', 'note' => 'Free, 24/7.', 'action' => 'Call 1018', 'url' => 'https://www.agim.gr/', 'primary' => true],
        ],
        'TR' => [
            ['name' => 'ALO 182', 'note' => 'Free, 24/7, Turkish.', 'action' => 'Call 182', 'url' => 'https://www.alo182.gov.tr/', 'primary' => true],
        ],
        'AE' => [
            ['name' => 'Yousef', 'note' => 'Free, 24/7 mental health support.', 'action' => 'Call 800 4673', 'url' => 'https://yousef.ae/', 'primary' => true],
        ],
        'SA' => [
            ['name' => 'Yasmine', 'note' => 'Free, 24/7, Arabic/English.', 'action' => 'Call 920033360', 'url' => 'https://yasmine.org.sa/', 'primary' => true],
        ],
        'KE' => [
            ['name' => 'Befrienders Kenya', 'note' => 'Free, 24/7.', 'action' => 'Call 1199', 'url' => 'https://befrienderskenya.org/', 'primary' => true],
        ],
        'NG' => [
            ['name' => 'TESSY', 'note' => 'Free, 24/7.', 'action' => 'Call 0800 200 0800', 'url' => 'https://tessy.ng/', 'primary' => true],
        ],
        'AR' => [
            ['name' => 'SAPS 135', 'note' => 'Free, 24/7.', 'action' => 'Call 135', 'url' => 'https://www.argentina.gob.ar/salud/sap', 'primary' => true],
        ],
        'CL' => [
            ['name' => 'Salud Responde', 'note' => 'Free, 24/7.', 'action' => 'Call 600 360 7777', 'url' => 'https://www.minsal.cl/', 'primary' => true],
        ],
        'CO' => [
            ['name' => 'Línea 106', 'note' => 'Free, 24/7.', 'action' => 'Call 106', 'url' => 'https://www.linea106.gov.co/', 'primary' => true],
        ],
    ];

    private const COUNTRY_NAMES = [
        'US' => 'the United States', 'CA' => 'Canada', 'GB' => 'the United Kingdom',
        'IE' => 'Ireland', 'AU' => 'Australia', 'NZ' => 'New Zealand', 'IN' => 'India',
        'DE' => 'Germany', 'FR' => 'France', 'ES' => 'Spain', 'IT' => 'Italy',
        'BR' => 'Brazil', 'MX' => 'Mexico', 'PH' => 'the Philippines', 'ZA' => 'South Africa',
        'NL' => 'the Netherlands', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'NO' => 'Norway',
        'DK' => 'Denmark', 'FI' => 'Finland', 'PL' => 'Poland', 'PT' => 'Portugal',
        'GR' => 'Greece', 'TR' => 'Turkey', 'AE' => 'the UAE', 'SA' => 'Saudi Arabia',
        'KE' => 'Kenya', 'NG' => 'Nigeria', 'AR' => 'Argentina', 'CL' => 'Chile',
        'CO' => 'Colombia',
    ];

    /** @return array<int,string> */
    public static function countryOptions(): array
    {
        return array_keys(self::COUNTRY_NAMES);
    }

    /**
     * Detect the visitor's country from their IP address.
     *
     * Order of preference:
     *  1. Cloudflare CF-IPCountry header — no external request, privacy-friendly
     *     because Cloudflare already sees the IP.
     *  2. ipwho.is free API — short timeout, cached in the session so we only
     *     call it once per visit.
     *
     * Returns null when detection fails or the country is not one we have
     * resources for. Callers must always fall back to the user's stored country
     * or US.
     */
    public static function detectedCountry(): ?string
    {
        if (isset($_SESSION['crisis_detected_country'])) {
            return $_SESSION['crisis_detected_country'];
        }

        // Cloudflare header — available on most managed hosts including
        // InfinityFree, no external request required.
        if (!empty($_SERVER['HTTP_CF_IPCountry'])) {
            $code = strtoupper(trim((string) $_SERVER['HTTP_CF_IPCountry']));
            if ($code !== '' && $code !== '--') {
                $_SESSION['crisis_detected_country'] = $code;
                return $code;
            }
        }

        $code = self::fetchCountryFromIP();
        if ($code !== null) {
            $_SESSION['crisis_detected_country'] = $code;
        }
        return $code;
    }

    private static function clientIP(): string
    {
        // Respect X-Forwarded-For when present (most shared hosts sit behind a
        // proxy), then fall back to REMOTE_ADDR.
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = array_map('trim', explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']));
            foreach ($parts as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    private static function fetchCountryFromIP(): ?string
    {
        $ip = self::clientIP();
        if ($ip === '') {
            return null;
        }
        $ch = curl_init('https://ipwho.is/' . urlencode($ip));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);

        if ($errno !== 0 || $body === false) {
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['success']) || empty($data['country_code'])) {
            return null;
        }

        return strtoupper((string) $data['country_code']);
    }

    /** Human-readable message shown in the UI when a risk level is detected. */
    public static function bannerText(string $level): string
    {
        switch ($level) {
            case self::LEVEL_CRITICAL:
                return 'It sounds like you may be in immediate danger. Please reach a human right now. You do not have to handle this alone.';
            case self::LEVEL_HIGH:
                return 'What you are describing sounds really heavy. Please talk to someone who can help today.';
            case self::LEVEL_ELEVATED:
                return 'You seem to be going through a lot right now. A few things below can help in the next few minutes.';
            default:
                return '';
        }
    }

    /**
     * Builds a plain-text safety plan the user can copy and send to someone
     * they trust. Works without email so it is useful on free hosting.
     */
    public static function safetyPlan(array $user): string
    {
        $country = $user['country'] ?? 'US';
        $res = self::resources($country);
        $lines = [];
        $lines[] = 'MY SAFETY PLAN';
        $lines[] = str_repeat('=', 32);
        $lines[] = 'Made with ' . (app_config('APP_NAME') ?? 'Quietroom') . ' on ' . date('j F Y');
        $lines[] = '';
        $lines[] = 'I am having a hard time and I want support. Please stay with me / check on me.';
        $lines[] = '';
        $lines[] = 'MY WARNING SIGNS';
        $lines[] = '_______________________________________________';
        $lines[] = '_______________________________________________';
        $lines[] = '';
        $lines[] = 'THINGS THAT HELP ME COPE (even a little)';
        $lines[] = '_______________________________________________';
        $lines[] = '_______________________________________________';
        $lines[] = '';
        $lines[] = 'PEOPLE I CAN CALL';
        $lines[] = '_______________________________________________';
        $lines[] = '_______________________________________________';
        $lines[] = '';
        $lines[] = 'PROFESSIONAL SUPPORT';
        $lines[] = 'Therapist / doctor: ________________________  Phone: ________________________';
        $lines[] = 'GP / primary care: ________________________  Phone: ________________________';
        $lines[] = '';
        $lines[] = 'CRISIS LINES';
        foreach ($res as $r) {
            $lines[] = '- ' . $r['name'] . ': ' . $r['action'];
        }
        $lines[] = '';
        $lines[] = 'If I am in immediate danger, I am going to call ' . self::emergency($country)['label'] . '.';
        $lines[] = str_repeat('=', 32);

        return implode("\n", $lines);
    }
}
