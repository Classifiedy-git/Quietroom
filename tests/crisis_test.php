<?php
/**
 * Crisis detection regression tests.
 *
 *   php tests/crisis_test.php
 *
 * Covers both directions: everyday phrases that must NOT trigger a crisis
 * banner, and real distress language that must.
 */
require dirname(__DIR__) . '/app/crisis.php';

$cases = [
    // [text, expected level, note]
    ['I want to kill time',                  'none',     'FP: kill time'],
    ['I am going to kill it at work',        'none',     'FP: kill it at work'],
    ['I want to end up somewhere nice',      'none',     'FP: end up'],
    ['I want to take my medication',         'none',     'FP: take meds'],
    ['I can take it',                        'none',     'FP: take it'],
    ['I will end the project next week',     'none',     'FP: end the project'],
    ['The meeting ended',                    'none',     'FP: ended'],
    ['I want to kill the weeds in the garden','none',    'FP: kill the weeds'],
    ['My phone battery is dying',            'none',     'FP: dying'],
    ['I am so tired of everything',          'none',     'FP: tired'],

    ['I want to kill myself',                'critical', 'TP: kill myself'],
    ['I am going to kill myself',            'critical', 'TP: going to kill myself'],
    ['planning to kill myself',              'critical', 'TP: planning kill myself'],
    ["I'm going to end it",                  'critical', 'TP: im going to end it'],
    ['I am going to end it',                 'critical', 'TP: going to end it'],
    ['I want to end it',                     'critical', 'TP: want to end it'],
    ['I am going to end my life',            'critical', 'TP: end my life'],
    ['I want to die',                        'critical', 'TP: want to die'],
    ['I do not want to be alive',            'critical', 'TP: dont want to be alive'],
    ['I wish I was dead',                    'critical', 'TP: wish dead'],
    ['I have planned to end it',             'critical', 'TP: planned to end it'],
    ['no point going on',                    'critical', 'TP: no point going on'],
    ['I am going to take my own life',       'critical', 'TP: take my own life'],
    ['I want to commit suicide',             'critical', 'TP: commit suicide'],
    ['I am going to kill them',              'critical', 'TP: harm others'],

    ['I cut myself last night',              'high',     'TP: self harm'],
    ['I feel completely hopeless',           'high',     'TP: hopeless'],
    ['I am not safe right now',              'high',     'TP: not safe'],
    ['I have been hurting myself',           'high',     'TP: hurting myself'],

    ['I am so overwhelmed by all of this',   'elevated', 'TP: overwhelmed'],
    ['This is overwhelming',                 'elevated', 'TP: overwhelming'],
    ["I can't take this anymore",            'high',     'TP: cant take this -> high'],
    ['I am falling apart',                   'elevated', 'TP: falling apart'],
    ['I feel completely numb',               'elevated', 'TP: numb'],
    ['I had a panic attack',                 'elevated', 'TP: panic attack'],
];

$fail = 0;
foreach ($cases as [$text, $expected, $note]) {
    $res  = Crisis::assess($text);
    $got  = $res['level'];
    $ok   = $got === $expected;
    if (!$ok) {
        $fail++;
    }
    printf(
        "%s  expected=%-9s got=%-9s  %-34s  cats=%s\n",
        $ok ? 'PASS' : 'FAIL',
        $expected,
        $got,
        $note,
        implode(',', $res['categories'])
    );
}

echo "\n" . ($fail === 0 ? "ALL PASS (" . count($cases) . " cases)\n" : "$fail FAILURES\n");

// --- tap-to-call links ------------------------------------------------------
// Every helpline that has a number must be diallable, with the digits only.
$dial = [
    ['Call 116 123',          ['tel:116123']],
    ['Call 911',              ['tel:911']],
    ['Call 999 / 112',       ['tel:999']],
    ['Call 13 11 14',        ['tel:131114']],
    ['Call 1300 659 467',    ['tel:1300659467']],
    ['Call 0800 543 135',    ['tel:0800543135']],
    ['Call 1860-2662-345',   ['tel:18602662345']],
    ['Call 1553 (landline)', ['tel:1553']],
    ['Call 0800 111 0 111',  ['tel:08001110111']],
    ['Call or text 988',     ['tel:988', 'sms:988']],
    ['Text HOME to 741741',  ['sms:741741?body=HOME']],
    ['Text SHOUT to 85258',  ['sms:85258?body=SHOUT']],
    ['Text 02 2327 2327',    ['sms:0223272327']],
    ['findahelpline.com',    []],
    ['psycom.sante.gouv.fr', []],
];

$dialFail = 0;
foreach ($dial as [$action, $expected]) {
    $got = array_column(Crisis::dialLinks(['action' => $action]), 'href');
    $ok  = $got === $expected;
    if (!$ok) {
        $dialFail++;
    }
    printf(
        "%s  %-24s %s\n",
        $ok ? 'PASS' : 'FAIL',
        $action,
        $got === [] ? '(no dial link)' : implode('  ', $got)
    );
}

echo "\n" . ($dialFail === 0 ? "ALL PASS (" . count($dial) . " dial cases)\n" : "$dialFail FAILURES\n");

// --- every country must have a diallable primary helpline -------------------
// A country whose headline helpline cannot be tapped is a country where
// someone in trouble has to read and type a number by hand.
$countryFail = 0;
foreach (Crisis::countryOptions() as $code) {
    $primary = array_values(array_filter(
        Crisis::resources($code),
        static fn($r) => !empty($r['primary'])
    ));
    $dialable = 0;
    foreach ($primary as $r) {
        $dialable += count(Crisis::dialLinks($r));
    }
    $ok = $dialable > 0;
    if (!$ok) {
        $countryFail++;
    }
    printf("%s  %-4s %-26s %d dialable\n", $ok ? 'PASS' : 'FAIL', $code, $primary[0]['name'] ?? '(no primary)', $dialable);
}

echo "\n" . ($countryFail === 0 ? "ALL PASS (" . count(Crisis::countryOptions()) . " countries)\n" : "$countryFail FAILURES\n");

// --- the emergency number must be the one for the country -------------------
// The crisis page's most important button used to say 911 for everyone, and
// 911 does not exist in most of the world. In the UK the right numbers are
// 999 and 112, so getting this wrong could cost someone a life.
$emergencyFail = 0;
foreach (Crisis::countryOptions() as $code) {
    $emergency = Crisis::emergency($code);
    $numbers   = $emergency['numbers'];
    $ok = !empty($numbers) && $numbers[0] !== '' && $emergency['label'] !== '';

    // Countries where 911 is not the number must not be given 911.
    $notNineOneOne = ['GB' => ['999'], 'IE' => ['999'], 'AU' => ['000'], 'NZ' => ['111'],
                       'ZA' => ['10111'], 'DE' => ['112'], 'FR' => ['112'], 'IN' => ['112'],
                       'IT' => ['112'], 'ES' => ['112'], 'BR' => ['190'], 'GR' => ['112'],
                       'SE' => ['112'], 'NO' => ['112'], 'DK' => ['112'], 'FI' => ['112'],
                       'PL' => ['112'], 'PT' => ['112'], 'TR' => ['112'], 'CH' => ['112'],
                       'NL' => ['112'], 'AE' => ['999'], 'SA' => ['999'], 'KE' => ['999'],
                       'NG' => ['112'], 'CL' => ['133'], 'CO' => ['123']];
    if (isset($notNineOneOne[$code]) && $numbers[0] === '911') {
        $ok = false;
    }

    if (!$ok) {
        $emergencyFail++;
    }
    printf("%s  %-4s %-12s (%s)\n", $ok ? 'PASS' : 'FAIL', $code, $emergency['label'], implode(' / ', $numbers));
}

// The UK must offer 999 as well as 112.
$gb = Crisis::emergency('GB');
if (!in_array('999', $gb['numbers'], true) || !in_array('112', $gb['numbers'], true)) {
    $emergencyFail++;
    echo "FAIL  GB must offer both 999 and 112\n";
} else {
    echo "PASS  GB offers 999 and 112\n";
}

// An unknown country must still get something to dial.
$unknown = Crisis::emergency('ZZ');
if (empty($unknown['numbers'][0])) {
    $emergencyFail++;
    echo "FAIL  unknown country has no emergency number\n";
} else {
    echo "PASS  unknown country falls back to " . $unknown['numbers'][0] . "\n";
}

echo "\n" . ($emergencyFail === 0 ? "ALL PASS (emergency numbers)\n" : "$emergencyFail FAILURES\n");

exit($fail === 0 && $dialFail === 0 && $countryFail === 0 && $emergencyFail === 0 ? 0 : 1);
