<?php
/**
 * Quietroom static therapeutic content: exercises, prompts, distortions.
 */

class Content
{
    /** @return array<string,array<string,mixed>> */
    public static function exercises(): array
    {
        return [
            'panic-mode' => [
                'name'       => 'Panic Mode',
                'category'   => 'Stabilise',
                'tag'        => 'For right now',
                'duration'   => 180,
                'icon'       => 'wind',
                'summary'    => 'A fast, guided reset for when a panic attack is building or already here.',
                'description'=> 'Everything in this exercise is designed to be done while you are in it. No thinking required. Just follow the circle and the words.',
                'type'       => 'breathing',
                'mode'       => 'panic',
                'phases'     => [
                    ['label' => 'Breathe in',  'seconds' => 4, 'text' => 'Through your nose. Let your belly widen.'],
                    ['label' => 'Hold',        'seconds' => 2, 'text' => 'Softly. Do not squeeze.'],
                    ['label' => 'Breathe out', 'seconds' => 6, 'text' => 'Slowly through your mouth, like fogging a mirror.'],
                ],
                'steps'      => [
                    'Put both feet flat on the floor. You do not need to close your eyes.',
                    'Name five things you can see. Say them silently.',
                    'Press your feet into the floor and say "I am here".',
                    'Now follow the circle: in for 4, hold for 2, out for 6.',
                    'Keep going until the circle ends. Then decide what you need next.',
                ],
                'note'       => 'If you feel faint, breathe normally instead and stop forcing the long hold.',
            ],

            'box-breathing' => [
                'name'       => 'Box Breathing',
                'category'   => 'Breathing',
                'tag'        => 'Before a hard moment',
                'duration'   => 240,
                'icon'       => 'square',
                'summary'    => 'Four counts in, four hold, four out, four hold. Used by people who perform under pressure.',
                'description'=> 'Box breathing balances your nervous system in about two minutes. It is steady and unremarkable, which is exactly why it works.',
                'type'       => 'breathing',
                'mode'       => 'box',
                'phases'     => [
                    ['label' => 'Breathe in',  'seconds' => 4, 'text' => 'Steady, even, through the nose.'],
                    ['label' => 'Hold',        'seconds' => 4, 'text' => 'Chest and shoulders stay relaxed.'],
                    ['label' => 'Breathe out', 'seconds' => 4, 'text' => 'Slow and complete, through the mouth.'],
                    ['label' => 'Hold',        'seconds' => 4, 'text' => 'Empty and still.'],
                ],
                'steps'      => [
                    'Sit or lie down. Loosen anything tight around your waist.',
                    'Rest the tip of your tongue behind your top teeth.',
                    'Follow the square: in, hold, out, hold.',
                    'Let the exhale be slightly longer than you think it needs to be.',
                ],
                'note'       => 'Four minutes is roughly ten full cycles.',
            ],

            'four-seven-eight' => [
                'name'       => '4-7-8 Breathing',
                'category'   => 'Breathing',
                'tag'        => 'For sleepless nights',
                'duration'   => 240,
                'icon'       => 'moon',
                'summary'    => 'A longer exhale to slow your heart rate and signal the body that it is safe to sleep.',
                'description'=> 'The long out-breath is the active ingredient: it shifts you from a fight-or-flight state towards rest.',
                'type'       => 'breathing',
                'mode'       => 'calm',
                'phases'     => [
                    ['label' => 'Breathe in',  'seconds' => 4, 'text' => 'Quietly through the nose.'],
                    ['label' => 'Hold',        'seconds' => 7, 'text' => 'Gently, without straining.'],
                    ['label' => 'Breathe out', 'seconds' => 8, 'text' => 'Very slowly, through the mouth with a soft whoosh.'],
                ],
                'steps'      => [
                    'Get comfortable. Ideally lie down.',
                    'Tongue behind your top teeth, lips lightly closed.',
                    'Do four cycles, then rest and breathe normally for one minute.',
                ],
                'note'       => 'If holding for 7 feels like too much, use 4-4-6 instead.',
            ],

            'physiological-sigh' => [
                'name'       => 'Physiological Sigh',
                'category'   => 'Breathing',
                'tag'        => 'Fastest way to settle',
                'duration'   => 120,
                'icon'       => 'lungs',
                'summary'    => 'A double inhale and a long exhale, the fastest researched way to calm an anxious nervous system.',
                'description'=> 'Your body has a built-in off-switch for stress. This exercise presses it: two quick inhales through the nose, then one long sigh out.',
                'type'       => 'breathing',
                'mode'       => 'sigh',
                'phases'     => [
                    ['label' => 'Inhale',    'seconds' => 2, 'text' => 'Two short sniffs through the nose, one after the other.'],
                    ['label' => 'Exhale',    'seconds' => 8, 'text' => 'One long, audible sigh through the mouth.'],
                ],
                'steps'      => [
                    'Take a normal breath in through the nose.',
                    'On top of it, take a second short sniff of air.',
                    'Now sigh it all out slowly through the mouth, like a long sigh of relief.',
                    'Repeat three to five times.',
                ],
                'note'       => 'Three sighs are usually enough to notice a change.',
            ],

            'grounding-54321' => [
                'name'       => '5-4-3-2-1 Grounding',
                'category'   => 'Grounding',
                'tag'        => 'When your mind races',
                'duration'   => 300,
                'icon'       => 'hand',
                'summary'    => 'Walk your attention back to your senses, one channel at a time.',
                'description'=> 'Grounding does not fix anything. It gives a racing mind something concrete to hold on to, and buys you the time to think clearly again.',
                'type'       => 'grounding',
                'mode'       => '54321',
                'phases'     => [
                    ['label' => 'Look',  'seconds' => 30, 'text' => 'Name 5 things you can see.'],
                    ['label' => 'Listen', 'seconds' => 30, 'text' => 'Name 4 things you can hear.'],
                    ['label' => 'Touch', 'seconds' => 30, 'text' => 'Name 3 things you can feel.'],
                    ['label' => 'Smell', 'seconds' => 30, 'text' => 'Name 2 things you can smell.'],
                    ['label' => 'Taste', 'seconds' => 30, 'text' => 'Name 1 thing you can taste.'],
                ],
                'steps'      => [
                    'Go slowly. There is no prize for speed.',
                    'For each one, actually look or listen. Do not just recall.',
                    'If your mind wanders, that is normal. Come back to the last sense.',
                ],
                'note'       => 'Works best when your eyes are open and you move slightly as you do it.',
            ],

            'cold-reset' => [
                'name'       => 'Cold Reset',
                'category'   => 'Grounding',
                'tag'        => 'Sharp, fast redirect',
                'duration'   => 90,
                'icon'       => 'droplet',
                'summary'    => 'Cold water on your face and wrists triggers a dive reflex that calms you fast.',
                'description'=> 'This is the strongest tool in the box for acute panic. It works in seconds because it acts on a reflex, not on your thinking.',
                'type'       => 'guided',
                'mode'       => 'cold',
                'phases'     => [
                    ['label' => 'Get ready',   'seconds' => 10, 'text' => 'Find cold water. A sink, a tap, a cold flannel.'],
                    ['label' => 'Face',        'seconds' => 20, 'text' => 'Splash cold water on your face. Keep your eyes open and breathe.'],
                    ['label' => 'Wrists',      'seconds' => 20, 'text' => 'Run cold water over the insides of your wrists.'],
                    ['label' => 'Breathe',     'seconds' => 40, 'text' => 'Slow, even breathing. You should feel the urge to sigh. Let it.'],
                ],
                'steps'      => [
                    'Never do this in a shower or bath. Sudden temperature change can be risky.',
                    'Breathe normally through your nose while the water is on you.',
                    'Afterwards, sit down. You will feel a big, natural sigh. That is the mechanism working.',
                ],
                'note'       => 'If you have a heart condition, dizziness, or are pregnant, skip this one.',
            ],

            'body-scan' => [
                'name'       => 'Body Scan',
                'category'   => 'Relaxation',
                'tag'        => 'For tension and poor sleep',
                'duration'   => 600,
                'icon'       => 'bed',
                'summary'    => 'A slow pass through your body, releasing tension you did not know you were holding.',
                'description'=> 'Anxiety shows up in the body long before the mind catches up. This scan gives your nervous system permission to switch off muscle tension.',
                'type'       => 'guided',
                'mode'       => 'scan',
                'phases'     => [
                    ['label' => 'Settle',  'seconds' => 60,  'text' => 'Let your eyes close. Three slow breaths. Feel the bed or chair under you.'],
                    ['label' => 'Feet & legs', 'seconds' => 90, 'text' => 'Notice your toes, ankles, calves, thighs. Unclench whatever is tight.'],
                    ['label' => 'Hips & stomach', 'seconds' => 90, 'text' => 'Soften your belly. Let your jaw be slightly open.'],
                    ['label' => 'Chest & hands', 'seconds' => 90, 'text' => 'Breathe into your ribs. Let your hands be heavy.'],
                    ['label' => 'Shoulders & jaw', 'seconds' => 90, 'text' => 'Drop your shoulders away from your ears. Release your teeth.'],
                    ['label' => 'Face & brow', 'seconds' => 90, 'text' => 'Smooth your forehead. Space between your eyebrows. Behind your eyes.'],
                    ['label' => 'Whole body', 'seconds' => 90, 'text' => 'See the whole shape of you, heavy and warm. Three slow breaths.'],
                ],
                'steps'      => [
                    'This works best lying down in a dark or dim room.',
                    'If you fall asleep, that is a good sign, not a failure.',
                ],
                'note'       => 'You can pause any time. There is no wrong way to do it.',
            ],

            'progressive-muscle' => [
                'name'       => 'Progressive Muscle Relaxation',
                'category'   => 'Relaxation',
                'tag'        => 'Physical tension release',
                'duration'   => 480,
                'icon'       => 'strength',
                'summary'    => 'Tense one muscle group hard, then let it go completely. The release is what calms you.',
                'description'=> 'This is a decades-old clinical technique. The signal to your nervous system is the contrast: tight, then not tight.',
                'type'       => 'guided',
                'mode'       => 'pmr',
                'phases'     => [
                    ['label' => 'Hands',   'seconds' => 60, 'text' => 'Clench your fists hard for 5 seconds… and release. Notice the difference.'],
                    ['label' => 'Arms',    'seconds' => 60, 'text' => 'Push both arms straight and tense for 5 seconds… and release.'],
                    ['label' => 'Shoulders', 'seconds' => 60, 'text' => 'Lift your shoulders toward your ears… and drop them.'],
                    ['label' => 'Face',    'seconds' => 60, 'text' => 'Scrunch your whole face, forehead to chin… and let go.'],
                    ['label' => 'Stomach', 'seconds' => 60, 'text' => 'Pull your stomach in and tighten… and release.'],
                    ['label' => 'Legs',    'seconds' => 60, 'text' => 'Tense your thighs and calves… and let go completely.'],
                    ['label' => 'Whole body', 'seconds' => 120, 'text' => 'Lie still. Feel the heaviness where the tension used to be.'],
                ],
                'steps'      => [
                    'Tense to about 70% of maximum, not maximum. Holding at full can cramp.',
                    'Hold for 5 seconds, then release for 15. The longer release does the work.',
                ],
                'note'       => 'Skip this if you have joint pain, a recent injury, or muscle spasms.',
            ],

            'self-compassion' => [
                'name'       => 'Self-Compassion Break',
                'category'       => 'Cognitive',
                'tag'        => 'When you are hard on yourself',
                'duration'   => 180,
                'icon'       => 'heart',
                'summary'    => 'Say to yourself what you would say to someone you love in the same situation.',
                'description'=> 'Self-criticism keeps you stuck: it feels like motivation, but it drives the anxiety loop. This breaks it by adding warmth.',
                'type'       => 'written',
                'mode'       => 'selfcompassion',
                'steps'      => [
                    'Notice the pain. Name it plainly: "This hurts", "I am struggling", "I feel alone."',
                    'Remember that everyone feels this way sometimes. You are not broken. This is a human difficulty.',
                    'Now say it as a caring friend: "This is really hard. It makes sense that I am struggling. I am not alone."',
                    'Ask: what would I say to my best friend in this exact situation? Say that to yourself.',
                    'Add one kind action: a warm drink, a blanket, ten minutes off your to-do list.',
                ],
                'note'       => 'It can feel fake at first. That is normal. It is a new channel, it needs practice.',
            ],

            'opposite-action' => [
                'name'       => 'Opposite Action',
                'category'   => 'Cognitive',
                'tag'        => 'For low mood and avoidance',
                'duration'   => 300,
                'icon'       => 'rotate',
                'summary'    => 'When your feeling drives you to withdraw, act in the opposite direction, gently and in small doses.',
                'description'=> 'If you feel low, low mood tells you to withdraw, which makes low mood worse. Opposite action breaks that loop before it sets in.',
                'type'       => 'written',
                'mode'       => 'opposite',
                'steps'      => [
                    'Name the feeling and what it is telling you to do (skip the gym, do not reply, stay in bed).',
                    'Name the opposite action, in the smallest possible version.',
                    'Do it for 5 minutes. That is enough. You can stop after.',
                    'Notice what the action says out loud: "I am not avoiding life, I am doing one small part of it."',
                ],
                'note'       => 'This is not about pretending you feel great. It is about not letting the feeling drive every decision.',
            ],

            'values-clarity' => [
                'name'       => 'Why Does It Matter?',
                'category'   => 'Cognitive',
                'tag'        => 'For meaning and motivation',
                'duration'   => 420,
                'icon'       => 'compass',
                'summary'    => 'Separate what is urgent from what is important, then connect today to what matters to you.',
                'description'=> 'Depression narrows the present. A values check widens it again by reminding you what you are actually working towards.',
                'type'       => 'written',
                'mode'       => 'values',
                'steps'      => [
                    'List the things that matter most to you: relationships, values, interests, people you want to be.',
                    'Pick the one that would matter most if it went well.',
                    'Write one small action today that moves a millimetre towards it. Ten minutes counts.',
                    'Write one sentence you would want to read in a month.',
                ],
                'note'       => 'Do this on a good day, and re-read it on a bad one.',
            ],

            'worry-time' => [
                'name'       => 'Scheduled Worry Time',
                'category'   => 'Cognitive',
                'tag'        => 'For worry that hijacks the day',
                'duration'   => 900,
                'icon'       => 'clock',
                'summary'    => 'Give worry a container, then deal with it on purpose instead of all day by accident.',
                'description'=> 'Unscheduled worry feels endless because it never gets finished. A fixed container makes it finite.',
                'type'       => 'written',
                'mode'       => 'worry',
                'steps'      => [
                    'Pick a fixed 15-minute slot later today. Write it down.',
                    'When worry appears before then, write one line: "Worry time is at 6pm" and come back to what you are doing.',
                    'At the slot, worry deliberately for 15 minutes.',
                    'At the end, sort each worry: is it actionable today, or is it a "no"?',
                ],
                'note'       => 'You do not have to solve every worry. Most of them just need to be put down.',
            ],

            'sleep-wind-down' => [
                'name'       => 'Sleep Wind-Down',
                'category'   => 'Relaxation',
                'tag'        => 'For racing thoughts at night',
                'duration'   => 1200,
                'icon'       => 'star',
                'summary'    => 'A 20-minute runway that gives your brain permission to let go of the day.',
                'description'=> 'You cannot force sleep. You can remove the reasons your body is still on alert at midnight.',
                'type'       => 'written',
                'mode'       => 'sleep',
                'steps'      => [
                    'Dim the lights an hour before bed. Screens off, or at least brightness down.',
                    'Write tomorrow’s three most important tasks on paper. Put the paper on a table. Your brain is looking for a way to hold on. Give it a place to put things down.',
                    'Write down any thought that keeps circling. Add: "I will deal with this at 9am."',
                    'Shower or wash your face in lukewarm water. Do 4-7-8 breathing for two minutes.',
                    'Keep the room cool and dark. If you are awake more than 20 minutes, get up, do something dull in dim light, and return when sleepy.',
                ],
                'note'       => 'Consistency of wake-up time matters more than consistency of bedtime.',
            ],

            'thought-record' => [
                'name'       => 'Thought Record',
                'category'   => 'Cognitive',
                'tag'        => 'The core CBT worksheet',
                'duration'   => 600,
                'icon'       => 'pen',
                'summary'    => 'Catch a distressing thought, examine the evidence, and write a fairer alternative.',
                'description'=> 'This is the single most researched self-help technique for anxiety and depression. Six questions, roughly ten minutes.',
                'type'       => 'thought',
                'mode'       => 'thought',
                'steps'      => [
                    'What was the situation? Where were you, what happened?',
                    'What emotion did you feel, and how strongly (0-100%)?',
                    'What automatic thought went through your head?',
                    'What facts support that thought?',
                    'What facts do not support it, or do you have evidence against it?',
                    'What is a fairer, more balanced alternative thought? How much do you believe it now?',
                ],
                'note'       => 'Optional: let the AI coach you through it afterwards.',
            ],

            'behavioral-activation' => [
                'name'       => 'Behavioural Activation Plan',
                'category'   => 'Behavioural',
                'tag'        => 'For motivation and low mood',
                'duration'   => 600,
                'icon'       => 'clipboard',
                'summary'    => 'Plan a series of small, doable actions that give your mood something to climb on.',
                'description'=> 'Action precedes motivation more often than the other way round. A plan of small wins is more reliable than waiting to feel like doing something.',
                'type'       => 'activation',
                'mode'       => 'activation',
                'steps'      => [
                    'List five things you would enjoy, however small: a shower, a walk to the corner, a specific song, a message to one person, opening a window.',
                    'Rate each for pleasure (1-10) and difficulty (1-10).',
                    'Start with the easiest one. Put it in your calendar as a real appointment with a time.',
                    'After doing it, tick it off and notice the smallest change. Even 5% counts.',
                    'Plan the next one before you leave the page. Momentum is the point.',
                ],
                'note'       => 'If your mood is very low, make the actions almost embarrassingly small. That is the strategy.',
            ],

            'cognitive-distortions' => [
                'name'       => 'Cognitive Distortion Check',
                'category'   => 'Cognitive',
                'tag'        => 'Learn to spot the pattern',
                'duration'   => 480,
                'icon'       => 'search',
                'summary'    => 'Learn the ten thinking traps that fuel anxiety and low mood, so you can catch them in the act.',
                'description'=> 'Once you can name the distortion, it loses a lot of its power. The thought does not change, but you stop treating it as the truth.',
                'type'       => 'written',
                'mode'       => 'distortions',
                'steps'      => [
                    'Read the ten distortions below.',
                    'Write down a thought from the last week for each one you recognise.',
                    'Mark the distortion you use most often. That is your personal hot spot.',
                    'For that one, write a more balanced version of the thought.',
                ],
                'note'       => 'Almost everyone has a favourite two or three. That is the point.',
            ],

            'balanced-reframe' => [
                'name'       => 'Balanced Reframe',
                'category'   => 'Cognitive',
                'tag'        => 'AI-assisted thinking',
                'duration'   => 240,
                'icon'       => 'sparkle',
                'summary'    => 'Give one anxious thought to the AI and get a fairer, evidence-based alternative back.',
                'description'=> 'A useful supplement to a thought record: you get a structured, balanced look at the thought without having to build the whole thing alone.',
                'type'       => 'ai',
                'mode'       => 'reframe',
                'steps'      => [
                    'Write down the thought exactly as it appears in your head.',
                    'Generate the reframe and read it slowly.',
                    'Do not accept it just because it is there. Find the part that is true and keep that.',
                    'Write your own version, in your own words, that you actually believe.',
                ],
                'note'       => 'The AI writes drafts. You decide what is true.',
            ],
        ];
    }

    /** @return array<int,array<string,string>> */
    public static function categories(): array
    {
        return [
            ['key' => 'Stabilise',    'icon' => 'shield', 'blurb' => 'For the next five minutes'],
            ['key' => 'Breathing',    'icon' => 'lungs', 'blurb' => 'Change your physiology'],
            ['key' => 'Grounding',    'icon' => 'hand', 'blurb' => 'Come back to the room'],
            ['key' => 'Relaxation',   'icon' => 'cloud', 'blurb' => 'Let the tension go'],
            ['key' => 'Cognitive',    'icon' => 'brain', 'blurb' => 'Look at the thought differently'],
            ['key' => 'Behavioural',  'icon' => 'footprints', 'blurb' => 'Do one small thing'],
            ['key' => 'Written',      'icon' => 'pen', 'blurb' => 'Prompts to write against'],
        ];
    }

    /** @return array<int,string> */
    public static function journalPrompts(): array
    {
        return [
            'What is taking up the most space in your head right now?',
            'What would you say to a friend who was having the day you are having?',
            'Name three things that are going right, however small.',
            'What did you do today that your anxiety would call a failure but you would call surviving?',
            'If your anxiety could talk, what is it trying to protect you from?',
            'What does the voice in your head sound like? Where do you think it learned to talk like that?',
            'What is one thing you are carrying that is not actually yours?',
            'Describe a moment today when you felt even slightly more like yourself.',
            'What is the smallest thing that would make tomorrow 5% easier?',
            'Write a letter to the version of you that is having the hardest week. What do they need to hear?',
            'What have you been avoiding, and what is the tiniest first step?',
            'List five things you did this week that you would not have thought possible a month ago.',
            'What is your brain trying to do for you right now, even if it is doing it badly?',
            'When did you last feel genuinely rested? What was different that day?',
            'What would you forgive yourself for if you knew nobody would ever judge you?',
        ];
    }

    /** @return array<string,string> */
    public static function distortions(): array
    {
        return [
            'All-or-nothing thinking' => 'Things are perfect or a disaster. "If it is not exactly right, it is a failure."',
            'Mind reading'            => 'You assume you know what others think of you, usually the worst version.',
            'Fortune telling'         => 'You predict the future negatively, as if it has already happened.',
            'Overgeneralisation'      => '"I always mess this up." One bad case becomes every case.',
            'Mental filter'           => 'You fixate on the one negative detail and ignore the rest.',
            'Disqualifying the positive' => '"They were just being polite." Good news does not count.',
            'Jumping to conclusions'  => 'You reach a conclusion (usually the worst) without the evidence.',
            'Emotional reasoning'      => 'You treat a feeling as a fact: "I feel like a failure, so I am one."',
            'Should statements'        => '"I should be further along by now." Rules about how you must feel and act.',
            'Labelling'               => '"I am an anxious mess." You turn one moment into your identity.',
            'Personalisation'         => 'You assume you caused things that had many causes.',
            'Control fallacy'         => 'You believe an outcome depends entirely on you, or not on you at all.',
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public static function checkinPrompts(): array
    {
        return [
            'mood'    => 'How is your mood right now?',
            'anxiety' => 'How anxious do you feel?',
            'energy'  => 'How much energy do you have?',
            'sleep'   => 'How many hours did you sleep last night?',
        ];
    }

    /** @return array<int,string> */
    public static function moodTriggers(): array
    {
        return [
            'Work / study', 'Relationships', 'Family', 'Health', 'Money', 'Sleep',
            'News / social media', 'Being alone', 'Crowds / social events', 'Uncertainty',
            'Conflict', 'Illness', 'Hormones', 'Substances', 'Weather', 'Something else',
        ];
    }

    /** @return array<int,array<string,string>> */
    public static function aiStyles(): array
    {
        return [
            ['key' => 'supportive', 'label' => 'Supportive companion', 'blurb' => 'Warm, steady, practical. Talks things through with you.'],
            ['key' => 'gentle',     'label' => 'Gentle guide',        'blurb' => 'Slower, gentler, more reflective. Fewer questions.'],
            ['key' => 'direct',     'label' => 'Direct coach',        'blurb' => 'Brief and structured. Helps you get to the next action.'],
        ];
    }
}
