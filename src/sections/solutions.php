
<div class="solutions-content">
    <p class="solutions-intro">
        Ljubljana leži v kotlini, kjer je prevetrenost pogosto slaba,
        zlasti ob temperaturnih inverzijah. Če želimo čistejši
        zrak, mora biti upravljanje mesta podrejeno naravnim dejstvom.
        Ljubljana si zaradi svoje geografske lege ne more privoščiti
        enakega pristopa k onesnaževanju kot mesta z ugodnejšimi pogoji - potrebuje učinkovitejše ukrepe.
    </p>

    <?php
    $measures = [
        [
            'icon' => 'car',
            'title' => 'UVEDBA NIZKOEMISIJSKIH CON',
            'description' => 'Postopna omejitev dostopa najbolj onesnažujočim vozilom na
            prometno obremenjenih območjih Ljubljane,
            glede na emisijski razred vozila.',
            'tags' => 'PROMET / NO₂ / PM2.5',
        ],
        [
            'icon' => 'tram',
            'title' => 'RAZVOJ MESTNEGA TIRNEGA PROMETA',
            'description' => 'Razvoj novih visokozmogljivih prometnih povezav, 
            na primer s tramvajskim omrežjem ali drugimi oblikami mestnega tirnega prevoza.',
            'tags' => 'JAVNI PREVOZ / NO₂ / PM',
        ],
        [
            'icon' => 'bus',
            'title' => 'PREDNOST JAVNEMU PREVOZU PRED AVTOMOBILI',
            'description' => 'Preureditev obstoječih voznih pasov v namenske avtobusne pasove.
            Prednost avtobusom na semaforjih,
            pogostejši odhodi in boljše povezave.',
            'tags' => 'JAVNI PREVOZ / NO₂ / PM',
        ],
        [
            'icon' => 'heating',
            'title' => 'ZMANJŠEVANJE EMISIJ IZ INDIVIDUALNIH KURIŠČ',
            'description' => 'Postopno zmanjševanje uporabe individualnih kurišč na trdna goriva.
            Spodbujanje ogrevanja brez lokalnih emisij iz zgorevanja.',
            'tags' => 'OGREVANJE / PM2.5 / PM10',
        ],
        [
            'icon' => 'wind',
            'title' => 'VAROVANJE PREZRAČEVALNIH KORIDORJEV',
            'description' => 'Ohranjanje odprtih površin, pomembnih za naravno prezračevanje Ljubljanske kotline.
            Strožje preverjanje vpliva novih gradenj na lokalne zračne tokove in preprečevanje posegov,
            ki bi dodatno ovirali razprševanje onesnaževal.',
            'tags' => 'URBANIZEM / PREZRAČEVANJE',
        ],
        [
            'icon' => 'alert',
            'title' => 'UKREPANJE OB VISOKI ONESNAŽENOSTI',
            'description' => 'Vnaprej določeni začasni ukrepi ob napovedanih epizodah visoke onesnaženosti
            ob temperaturnih inverzijah, npr. omejitve najbolj onesnažujočega prometa.',
            'tags' => 'IZREDNI UKREPI / PM2.5 / NO₂',
        ],
    ];

    $icons = [
        'car' => '<rect x="4" y="10" width="16" height="8" rx="1"/><path d="M6 10l2-5h8l2 5M4 14H2m20 0h-2M6 18v2m12-2v2"/><circle cx="7.5" cy="14" r="1"/><circle cx="16.5" cy="14" r="1"/>',
        'tram' => '<rect x="5" y="3" width="14" height="15" rx="2"/><path d="M8 3V1m8 2V1M5 10h14M8 21l2-3m6 3-2-3M8 14h2m4 0h2"/>',
        'bus' => '<rect x="4" y="3" width="16" height="17" rx="2"/><path d="M4 11h16M8 20v2m8-2v2M8 15h2m4 0h2"/><path d="M8 7h8"/>',
        'heating' => '<path d="M12 22c4.5 0 7-3.2 7-7 0-3-1.5-5-4-7 .2 3-1 4-2 4 0-4-1-7-4-10 .5 4-4 6-4 13 0 3.8 2.5 7 7 7Z"/><path d="M12 22c2.2 0 3.5-1.5 3.5-3.5 0-1.8-1-3-2.5-4-.1 2-2.5 3-2.5 4.5 0 1.8.5 3 1.5 3Z"/>',
        'wind' => '<path d="M3 8h12a3 3 0 1 0-3-3M2 12h18a2 2 0 1 1-2 2M4 16h8a3 3 0 1 1-3 3"/>',
        'alert' => '<path d="M12 3 2 21h20L12 3Z"/><path d="M12 9v5m0 3v.5"/>',
    ];
    ?>

    <div class="solutions-block">
        <span class="eyebrow">04.1 / UKREPI</span>

        <div class="solutions-measures">
            <?php foreach ($measures as $measure): ?>
                <article class="measure-row">
                    <div class="measure-icon" aria-hidden="true">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <?= $icons[$measure['icon']] ?>
                        </svg>
                    </div>

                    <div class="measure-body">
                        <h3>
                            <?= htmlspecialchars(
                                $measure['title'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars(
                                $measure['description'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <span class="measure-tags">
                            <?= htmlspecialchars(
                                $measure['tags'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="solutions-block">
        <span class="eyebrow">
            04.2 / ZAHTEVAJ SPREMEMBE
        </span>

        <div class="solutions-contact">

            <div class="solutions-contact-copy">
                <h3>
                    SPREMEMBE LAHKO UVEDE LE MESTNA OBLAST, PIŠI JI.
                </h3>

                <p>
                    Čistejši zrak zahteva politične odločitve.
                    Sporoči mestnim oblastem, katere ukrepe
                    podpiraš in želiš videti v prihodnosti.
                </p>
            </div>

            <div class="solutions-actions">
                <a
                    class="solutions-action"
                    href="https://pobude.ljubljana.si/"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    ODDAJ POBUDO NA STRANI MOL ↗
                </a>

                <a
                    class="solutions-action solutions-action-secondary"
                    href="mailto:glavna.pisarna@ljubljana.si?subject=Kakovost%20zraka%20v%20Ljubljani"
                >
                    POŠLJI E-POŠTO NA GLAVNO PISARNO MOL ↗
                </a>
            </div>

        </div>
    </div>

</div>
