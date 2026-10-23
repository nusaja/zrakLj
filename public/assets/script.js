
'use strict';

/**
 * zrakLJ — JavaScript
 *
 * Geolokacija, navigacija, zgodovinski graf,
 * urni indeks EEA in dnevne smernice WHO.
 *
 * Referenčne koncentracije prihajajo iz PHP konfiguracije.
 */

// ==========================================
// 1. MERILNE POSTAJE
// ==========================================

const STATION_LOCATIONS = {
    bezigrad: {
        lat: 46.0655449,
        lon: 14.5127203
    },
    sis: {
        lat: 46.0720916,
        lon: 14.4871183
    },
    vic: {
        lat: 46.037487,
        lon: 14.4892717
    }
};

// ==========================================
// 2. IZRAČUN RAZDALJE
// ==========================================

function distanceBetween(lat1, lon1, lat2, lon2) {
    const toRadians = degrees => degrees * Math.PI / 180;
    const earthRadius = 6371000;

    const deltaLat = toRadians(lat2 - lat1);
    const deltaLon = toRadians(lon2 - lon1);

    const a =
        Math.sin(deltaLat / 2) ** 2 +
        Math.cos(toRadians(lat1)) *
        Math.cos(toRadians(lat2)) *
        Math.sin(deltaLon / 2) ** 2;

    return 2 * earthRadius * Math.atan2(
        Math.sqrt(a),
        Math.sqrt(1 - a)
    );
}

function findNearestStation(lat, lon) {
    let nearestArea = null;
    let shortestDistance = Infinity;

    for (const [area, station] of Object.entries(STATION_LOCATIONS)) {
        const distance = distanceBetween(
            lat,
            lon,
            station.lat,
            station.lon
        );

        if (distance < shortestDistance) {
            shortestDistance = distance;
            nearestArea = area;
        }
    }

    return nearestArea;
}

// ==========================================
// 3. GEOLOKACIJA
// ==========================================



function markNearestStation(area) {
  const label = document.getElementById('geo-selected-label');
  let found = false;

  document.querySelectorAll('.station-card[data-area]')
      .forEach(card => {
          const isNearest = card.dataset.area === area;

          card.classList.remove('is-nearest');

          if (isNearest) {
              void card.offsetWidth;
              card.classList.add('is-nearest');
          }

          if (isNearest) {
              found = true;
          }
      });

  if (label) {
      label.textContent = 'NAJBLIŽJA';
      label.hidden = !found;
  }

  return found;
}


function tryGeolocate() {
    const button = document.getElementById('geolocate');
    const label = document.getElementById('geo-selected-label');

    const MAX_ACCURACY = 1000; // metri
    const MAX_WAIT = 10000;    // 10 sekund

    const showStatus = text => {
        if (label) {
            label.textContent = text;
            label.hidden = false;
        }
    };

    // Odstrani oznako prejšnje geolokacije.
    document.querySelectorAll('.station-card[data-area]')
        .forEach(card => card.classList.remove('is-nearest'));

    if (!navigator.geolocation) {
        showStatus('GEOLOKACIJA NI PODPRTA');
        return;
    }

    if (!window.isSecureContext) {
        showStatus('GEOLOKACIJA ZAHTEVA HTTPS');
        return;
    }

    if (button) {
        button.disabled = true;
    }

    showStatus('PRIDOBIVAM LOKACIJO ...');

    let watchId = null;
    let finished = false;

    const finish = message => {
        if (finished) return;

        finished = true;
        clearTimeout(timer);

        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
        }

        if (button) {
            button.disabled = false;
        }

        if (message) {
            showStatus(message);
        }
    };

    const timer = setTimeout(() => {
        finish('VAŠA NAPRAVA NE MORE NATANČNO DOLOČITI LOKACIJE');
    }, MAX_WAIT);

    watchId = navigator.geolocation.watchPosition(
        position => {
            if (finished) return;

            const { latitude, longitude, accuracy } = position.coords;

            // Ne uporabljaj nenatančnih koordinat.
            if (
                !Number.isFinite(latitude) ||
                !Number.isFinite(longitude) ||
                !Number.isFinite(accuracy) ||
                accuracy > MAX_ACCURACY
            ) {
                showStatus('PRIDOBIVAM LOKACIJO ...');
                return;
            }

            const nearestArea = findNearestStation(
                latitude,
                longitude
            );

            const found = markNearestStation(nearestArea);

            finish(
                found
                    ? null
                    : 'POSTAJE NI MOGOČE OZNAČITI'
            );
        },

        error => {
            if (finished) return;

            const messages = {
                1: 'DOSTOP DO LOKACIJE NI DOVOLJEN',
                2: 'LOKACIJA NI NA VOLJO',
                3: 'PRIDOBIVANJE LOKACIJE JE POTEKLO'
            };

            // Ob začasni napaki počakaj do skupne omejitve.
            if (error.code === 2 || error.code === 3) {
                showStatus('PRIDOBIVAM LOKACIJO ...');
                return;
            }

            finish(messages[error.code] || 'NAPAKA GEOLOKACIJE');
        },

        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }
    );

    // Če je bila lokacija vrnjena zelo hitro,
    // prepreči, da bi spremljanje ostalo aktivno.
    if (finished && watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
    }
}


function initializeStationSelection() {
  const geoButton = document.getElementById('geolocate');

  if (geoButton) {
      geoButton.addEventListener('click', tryGeolocate);
  }
}


// ==========================================
// 4. NAVIGACIJA MED SEKCIJAMI
// ==========================================

function initializeSectionNavigation() {
    const navigation = document.getElementById(
        'section-navigation'
    );

    if (!navigation) {
        return;
    }

    const links = Array.from(
        navigation.querySelectorAll('a[href^="#"]')
    );

    const sections = Array.from(
        document.querySelectorAll('main .content-section')
    );


    function showSection(sectionId) {
      const target = sections.find(
          section => section.id === sectionId
      );
  
      if (!target) {
          return;
      }
  
      sections.forEach(section => {
          const isActive = section === target;
  
          section.classList.toggle('is-active', isActive);
          section.hidden = !isActive;
      });
  
      links.forEach(link => {
          const isActive =
              link.getAttribute('href') === '#' + sectionId;
  
          link.classList.toggle('is-active', isActive);
  
          if (isActive) {
              link.setAttribute('aria-current', 'page');
          } else {
              link.removeAttribute('aria-current');
          }
      });
  
      requestAnimationFrame(() => {
          if (
              sectionId === 'grafi' &&
              window.historyChart
          ) {
              window.historyChart.resize();
          }
  
          if (
              sectionId === 'dolgorocno' &&
              typeof Chart !== 'undefined'
          ) {
              const annualChart = Chart.getChart('annualChart');
  
              if (annualChart) {
                  annualChart.resize();
              }
          }
      });
  }
  

    links.forEach(link => {
        link.addEventListener('click', function (event) {
            const sectionId =
                link.getAttribute('href').slice(1);

            const exists = sections.some(
                section => section.id === sectionId
            );

            if (!exists) {
                return;
            }

            event.preventDefault();
            showSection(sectionId);

            const url = new URL(window.location.href);
            url.hash = sectionId;

            window.history.replaceState(
                null,
                '',
                url.pathname + url.search + url.hash
            );
        });
    });

    const initialSection = window.location.hash.slice(1);

    const validInitialSection = sections.some(
        section => section.id === initialSection
    );

    showSection(
        validInitialSection ? initialSection : 'trenutno'
    );

    window.addEventListener('popstate', function () {
        const sectionId = window.location.hash.slice(1);

        const exists = sections.some(
            section => section.id === sectionId
        );

        showSection(exists ? sectionId : 'trenutno');
    });
}

// ==========================================
// 5. BRANJE JSON PODATKOV
// ==========================================

function readJsonElement(id) {
    const element = document.getElementById(id);

    if (!element) {
        return null;
    }

    try {
        const data = JSON.parse(element.textContent || '{}');

        return data &&
            typeof data === 'object' &&
            !Array.isArray(data)
            ? data
            : null;
    } catch (error) {
        console.error(
            `Neveljavni JSON podatki: ${id}`,
            error
        );

        return null;
    }
}

// ==========================================
// 6. REFERENČNE VREDNOSTI
// ==========================================

/**
 * Vrne konfiguracijo za izbrano onesnaževalo
 * in časovno obdobje.
 *
 * Koncentracijski pragovi so samo v config.php.
 */
function getChartReference(config, pollutant, isHourly) {
    const pollutantConfig = config.pollutants?.[pollutant];

    if (!pollutantConfig) {
        return null;
    }

    if (isHourly) {
        const thresholds = config.eea?.thresholds?.[pollutant];
        const categories = config.eea?.categories;

        if (
            !Array.isArray(thresholds) ||
            thresholds.length !== 5 ||
            !Array.isArray(categories) ||
            categories.length !== 6
        ) {
            return null;
        }

        return {
            type: 'eea',
            thresholds,
            categories,
            pollutant,
            label: pollutantConfig.label
        };
    }

    const period = pollutantConfig.who_period;
    const source = pollutantConfig.who_source;

    const guidelines = source === 'legacy'
        ? config.who?.legacyGuidelines
        : config.who?.guidelines2021;

    const value = guidelines?.[pollutant]?.[period];

    if (
        typeof value !== 'number' ||
        !Number.isFinite(value) ||
        value < 0
    ) {
        return null;
    }

    return {
        type: 'who',
        value,
        period,
        source,
        pollutant,
        label: pollutantConfig.label,
        dailyStatistic: pollutantConfig.daily_statistic
    };
}

function getStatisticDescription(statistic) {
    const descriptions = {
        '24h': '24-urno povprečje',
        'max_8h': 'največje 8-urno drseče povprečje v dnevu',
        'max_1h': 'največja urna koncentracija v dnevu'
    };

    return descriptions[statistic] || statistic;
}

/**
 * Informacijsko okence za EEA in WHO.
 * Vsebino ustvarjamo z DOM metodami.
 */


function createReferenceDescription(reference) {
  const fragment = document.createDocumentFragment();

  function addParagraph(text, isHeading = false) {
      const element = document.createElement(
          isHeading ? 'strong' : 'p'
      );

      element.textContent = text;
      fragment.appendChild(element);
  }

  if (reference.type === 'eea') {
      addParagraph(
          'EVROPSKI INDEKS KAKOVOSTI ZRAKA',
          true
      );

      addParagraph(
          'Barvna območja temeljijo na evropskem indeksu kakovosti zraka (EEA). ' +
          'Vsako onesnaževalo ima svoje mejne vrednosti za posamezne kategorije ' +
          'od dobre do izredno slabe kakovosti zraka.'
      );

      const legend = document.createElement('div');
      legend.className = 'chart-reference-legend';

      const legendItems = [
          {
              indices: [0],
              name: 'DOBRO',
              description: 'Nizka onesnaženost zraka. Običajne aktivnosti na prostem.'
          },
          {
              indices: [1],
              name: 'ZADOVOLJIVO',
              description: 'Sprejemljiva onesnaženost zraka. Običajne aktivnosti na prostem.'
          },
          {
              indices: [2],
              name: 'ZMERNO',
              description: 'Povečana onesnaženost zraka. Odsvetovana intenzivna vadba na prostem za občutljive skupine.'
          },
          {
              indices: [3, 4, 5],
              name: 'SLABO / ZELO SLABO / IZREDNO SLABO',
              description: 'Visoka do izredno visoka onesnaženost zraka. Odsvetovana intenzivna vadba na prostem.'
          }
      ];

      legendItems.forEach(item => {
          const row = document.createElement('div');
          row.className = 'chart-reference-legend-row';

          const swatch = document.createElement('span');
          swatch.className = 'chart-reference-legend-color';
          swatch.setAttribute('aria-hidden', 'true');

          const category = reference.categories?.[item.indices[0]];

          if (category?.color) {
              swatch.style.backgroundColor = category.color;
          }

          const content = document.createElement('div');
          content.className = 'chart-reference-legend-content';

          const name = document.createElement('span');
          name.className = 'chart-reference-legend-name';
          name.textContent = item.name;

          const description = document.createElement('span');
          description.className = 'chart-reference-legend-description';
          description.textContent = item.description;

          content.append(name, description);
          row.append(swatch, content);
          legend.appendChild(row);
      });

      fragment.appendChild(legend);

      return fragment;
  }

  addParagraph(
      `ZDRAVSTVENA SMERNICA WHO: ${reference.label}`,
      true
  );

  addParagraph(
      `Črtkana črta predstavlja 24-urno zdravstveno smernico WHO: ` +
      `${reference.value} µg/m³.`
  );

  addParagraph(
    'Smernice WHO temeljijo na znanstvenih dokazih o škodljivih vplivih onesnaženega zraka na zdravje. ' 
  );

  addParagraph(
    'Zakonske mejne vrednosti, ki veljajo v Sloveniji, so manj stroge od zdravstvenih priporočil WHO. ' 
  );

  addParagraph(
    'Za delce PM2.5, ki sodijo med zdravju najbolj škodljiva onesnaževala in prodrejo globoko v pljuča, Slovenija sploh nima dnevne mejne vrednosti. '
  );

  addParagraph(
    'Nova EU zakonodaja Slovenijo zavezuje k uvedbi dnevne meje 25 µg/m³ za leto 2030, ki pa ostaja višja od zdravstvenega priporočila WHO (15 µg/m³).'
  );

  return fragment;
}

// ==========================================
// 7. INFORMACIJSKO OKENCE GRAFA
// ==========================================

function initializeChartReferenceInfo() {
    const container = document.getElementById('chart-reference');
    const symbol = document.getElementById('chart-reference-symbol');
    const label = document.getElementById('chart-reference-label');
    const infoButton = document.getElementById('chart-reference-info');
    const popover = document.getElementById('chart-reference-popover');
    const closeButton = document.getElementById('chart-reference-close');
    const description = document.getElementById(
        'chart-reference-description'
    );

    if (
        !container ||
        !symbol ||
        !label ||
        !infoButton ||
        !popover ||
        !closeButton ||
        !description
    ) {
        return {
            update() {},
            close() {}
        };
    }

    function close() {
        popover.hidden = true;
        infoButton.setAttribute('aria-expanded', 'false');
    }

    function open() {
        popover.hidden = false;
        infoButton.setAttribute('aria-expanded', 'true');
    }

    infoButton.addEventListener('click', () => {
        if (popover.hidden) {
            open();
        } else {
            close();
        }
    });

    closeButton.addEventListener('click', () => {
        close();
        infoButton.focus();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !popover.hidden) {
            close();
            infoButton.focus();
        }
    });

    document.addEventListener('click', event => {
        if (
            !popover.hidden &&
            !container.contains(event.target)
        ) {
            close();
        }
    });

    function update(reference) {
        close();

        container.hidden = !reference;

        if (!reference) {
            description.replaceChildren();
            return;
        }

        const isHourly = reference.type === 'eea';

        symbol.classList.toggle('is-eea', isHourly);
        symbol.classList.toggle('is-who', !isHourly);

        if (isHourly) {
            label.textContent =
                'BARVNA OBMOČJA: KATEGORIJE EEA';
        } else {
            label.textContent =
                reference.source === 'legacy'
                    ? 'ČRTKANA ČRTA: STAREJŠA SMERNICA WHO'
                    : 'ČRTKANA ČRTA: ZDRAVSTVENA SMERNICA WHO';
        }

        description.replaceChildren(
            createReferenceDescription(reference)
        );
    }

    return { update, close };
}

// ==========================================
// 8. GRAF — REFERENČNA OBMOČJA
// ==========================================

/**
 * Chart.js plugin:
 * EEA — barvni pasovi in meje kategorij.
 * WHO — ena črtkana vodoravna črta.
 */
function createChartReferencePlugin(getReference) {
    return {
        id: 'airQualityReferences',

        beforeDatasetsDraw(chart) {
            const reference = getReference();

            if (!reference) {
                return;
            }

            const { ctx, chartArea, scales } = chart;
            const y = scales.y;

            if (!chartArea || !y) {
                return;
            }

            ctx.save();

            ctx.beginPath();
            ctx.rect(
                chartArea.left,
                chartArea.top,
                chartArea.width,
                chartArea.height
            );
            ctx.clip();

            if (reference.type === 'eea') {
                const boundaries = [
                    0,
                    ...reference.thresholds,
                    Math.max(
                        y.max,
                        reference.thresholds[
                            reference.thresholds.length - 1
                        ]
                    )
                ];

                reference.categories.forEach(
                    (category, index) => {
                        const lower = boundaries[index];
                        const upper = boundaries[index + 1];

                        const top = y.getPixelForValue(upper);
                        const bottom = y.getPixelForValue(lower);

                        ctx.globalAlpha = 0.25;
                        ctx.fillStyle = category.color;

                        ctx.fillRect(
                            chartArea.left,
                            top,
                            chartArea.width,
                            bottom - top
                        );
                    }
                );

                ctx.globalAlpha = 1;
                ctx.strokeStyle = 'rgba(23, 23, 23, 0.20)';
                ctx.lineWidth = 0.8;
                ctx.setLineDash([3, 5]);

                reference.thresholds.forEach(value => {
                    if (value > y.max) {
                        return;
                    }

                    const pixel = y.getPixelForValue(value);

                    ctx.beginPath();
                    ctx.moveTo(chartArea.left, pixel);
                    ctx.lineTo(chartArea.right, pixel);
                    ctx.stroke();
                });
            }

            if (reference.type === 'who') {
                const pixel = y.getPixelForValue(reference.value);

                ctx.globalAlpha = 1;
                ctx.strokeStyle = '#263F56';                
                ctx.lineWidth = 1.5;
                ctx.setLineDash([7, 5]);

                ctx.beginPath();
                ctx.moveTo(chartArea.left, pixel);
                ctx.lineTo(chartArea.right, pixel);
                ctx.stroke();
            }

            ctx.restore();
        }
    };
}

// ==========================================
// 9. ZGODOVINSKI GRAF
// ==========================================

function initializeHistoryChart() {
    const canvas = document.getElementById('historyChart');
    const chartNote = document.getElementById('chart-note');
    const pollutantSelect = document.getElementById('pollutant');
    const fallback = document.getElementById('chart-fallback');

    const rangeButtons = document.querySelectorAll('[data-days]');

    if (!canvas || !pollutantSelect) {
        return;
    }

    function showFallback(message) {
        canvas.hidden = true;

        if (fallback) {
            fallback.hidden = false;
            fallback.textContent = message;
        }
    }

    if (typeof Chart === 'undefined') {
        showFallback(
            'Knjižnice za prikaz grafa ni bilo mogoče naložiti.'
        );
        return;
    }

    const history = readJsonElement('daily-data');
    const hourlyHistory = readJsonElement('hourly-data') || {};
    const referenceConfig = readJsonElement('chart-reference-data');

    if (!history) {
        showFallback(
            'Zgodovinskih podatkov ni mogoče prebrati.'
        );
        return;
    }

    const hourlyDates = Object.keys(hourlyHistory)
        .filter(date =>
            /^\d{4}-\d{2}-\d{2} \d{2}:00$/.test(date)
        )
        .sort();

    const allDates = Object.keys(history)
        .filter(date =>
            /^\d{4}-\d{2}-\d{2}$/.test(date)
        )
        .sort();

    if (allDates.length === 0 && hourlyDates.length === 0) {
        showFallback(
            'Za izbrano obdobje ni zgodovinskih meritev.'
        );
        return;
    }

    const stations = [
        {
            name: 'LJ Bežigrad',
            color: '#222222'
        },
        {
            name: 'LJ Celovška',
            color: '#C17853'
        },
        {
            name: 'LJ Vič',
            color: '#827D87'
        }
    ];

    let selectedDays = '24h';

    const referenceInfo = initializeChartReferenceInfo();

    function getCurrentReference() {
        if (!referenceConfig) {
            return null;
        }

        return getChartReference(
            referenceConfig,
            pollutantSelect.value,
            selectedDays === '24h'
        );
    }

    function formatDate(dateString) {
        const parts = dateString.split('-');

        if (parts.length !== 3) {
            return dateString;
        }

        return Number(parts[2]) + '.' +
            Number(parts[1]) + '.';
    }

    function getDates(days) {
        if (allDates.length === 0) {
            return [];
        }

        const lastDate = allDates[allDates.length - 1];

        const [year, month, day] = lastDate
            .split('-')
            .map(Number);

        const endDate = new Date(year, month - 1, day);
        const dates = [];

        for (let i = days - 1; i >= 0; i--) {
            const date = new Date(
                endDate.getFullYear(),
                endDate.getMonth(),
                endDate.getDate() - i
            );

            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');

            dates.push(`${y}-${m}-${d}`);
        }

        return dates;
    }

    function getHourlyDates() {
        if (hourlyDates.length === 0) {
            return [];
        }

        const last = hourlyDates[hourlyDates.length - 1];
        const [datePart, timePart] = last.split(' ');
        const [year, month, day] = datePart.split('-').map(Number);
        const hour = Number(timePart.slice(0, 2));

        const end = new Date(year, month - 1, day, hour);
        const dates = [];

        for (let i = 23; i >= 0; i--) {
            const date = new Date(
                end.getTime() - i * 3600000
            );

            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            const h = String(date.getHours()).padStart(2, '0');

            dates.push(`${y}-${m}-${d} ${h}:00`);
        }

        return dates;
    }

    function getChartDates() {
        return selectedDays === '24h'
            ? getHourlyDates()
            : getDates(selectedDays);
    }

    function getMeasurement(date, station, pollutant) {
        const source = selectedDays === '24h'
            ? hourlyHistory
            : history;

        const raw = source[date]?.[station]?.[pollutant];

        if (raw === null || raw === undefined || raw === '') {
            return null;
        }

        const text = String(raw).trim().replace(',', '.');

        const match = text.match(/^([<>])\s*(\d+(?:\.\d+)?)$/);

        const value = Number(match ? match[2] : text);

        if (!Number.isFinite(value) || value < 0) {
            return null;
        }

        return value;
    }

    function createDatasets(dates, pollutant) {
        return stations.map(station => ({
            label: station.name,

            data: dates.map(date =>
                getMeasurement(
                    date,
                    station.name,
                    pollutant
                )
            ),

            borderColor: station.color,
            backgroundColor: station.color,
            borderWidth: 2,

            pointRadius: selectedDays >= 90 ? 0 : 3,
            pointHoverRadius: 5,
            pointHitRadius: 10,

            tension: 0.25,
            spanGaps: false
        }));
    }

    // ======================================
    // OZADJE VIKENDOV
    // ======================================

    const weekendBackground = {
        id: 'weekendBackground',

        beforeDraw(chart) {
            if (selectedDays === '24h') {
                return;
            }

            const { ctx, chartArea, scales } = chart;
            const x = scales.x;

            if (!chartArea || !x) {
                return;
            }

            const dates = getDates(selectedDays);

            ctx.save();
            ctx.fillStyle = '#F4F1EA';  

            if (Number(selectedDays) < 90) {          
            dates.forEach((dateString, index) => {
                const [year, month, day] = dateString
                    .split('-')
                    .map(Number);

                const weekday = new Date(
                    year,
                    month - 1,
                    day
                ).getDay();

                if (weekday !== 0 && weekday !== 6) {
                    return;
                }

                const center = x.getPixelForValue(index);

                const left = index === 0
                    ? chartArea.left
                    : (
                        x.getPixelForValue(index - 1) +
                        center
                    ) / 2;

                const right = index === dates.length - 1
                    ? chartArea.right
                    : (
                        center +
                        x.getPixelForValue(index + 1)
                    ) / 2;

                ctx.fillRect(
                    left,
                    chartArea.top,
                    right - left,
                    chartArea.bottom - chartArea.top
                );
            });
          };

            if (selectedDays >= 90) {
                ctx.strokeStyle = 'rgba(23, 23, 23, 0.25)';
                ctx.lineWidth = 1;

                dates.forEach((dateString, index) => {
                    const day = Number(
                        dateString.split('-')[2]
                    );

                    if (day !== 1 || index === 0) {
                        return;
                    }

                    const previous = x.getPixelForValue(index - 1);
                    const current = x.getPixelForValue(index);
                    const boundary = (previous + current) / 2;

                    ctx.beginPath();
                    ctx.moveTo(boundary, chartArea.top);
                    ctx.lineTo(boundary, chartArea.bottom);
                    ctx.stroke();
                });
            }

            ctx.restore();
        }
    };

    const referencePlugin = createChartReferencePlugin(
        getCurrentReference
    );

    const initialDates = getDates(selectedDays);

    // ======================================
    // CHART.JS
    // ======================================

    const chart = new Chart(canvas, {
        type: 'line',

        plugins: [
            weekendBackground,
            referencePlugin
        ],

        data: {
            labels: initialDates.map(formatDate),

            datasets: createDatasets(
                initialDates,
                pollutantSelect.value
            )
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                mode: 'index',
                intersect: false
            },

            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',

                    labels: {
                        usePointStyle: true,
                        boxWidth: 10,
                        padding: 20,
                        color: '#222222',

                        font: {
                            family: 'Roboto Mono, monospace',
                            size: 11
                        },

                        generateLabels(chart) {
                            return Chart.defaults.plugins.legend.labels
                                .generateLabels(chart)
                                .map(item => ({
                                    ...item,
                                    text: '\u00A0\u00A0' + item.text
                                }));
                        }
                    }
                },

                tooltip: {
                    callbacks: {
                        title(items) {
                            if (!items.length) {
                                return '';
                            }

                            const index = items[0].dataIndex;
                            const dates = getChartDates();
                            const date = dates[index] || '';

                            if (selectedDays === '24h' && date) {
                                const [ymd, time] = date.split(' ');
                                const [year, month, day] = ymd.split('-');

                                const startHour = Number(time.slice(0, 2));
                                const endHour = (startHour + 1) % 24;

                                const from = `${String(startHour).padStart(2, '0')}:00`;
                                const to = `${String(endHour).padStart(2, '0')}:00`;

                                return `${day}. ${month}. ${year} · ${from}–${to}`;
                            }

                            return date;
                        },

                        label(context) {
                            if (context.parsed.y === null) {
                                return context.dataset.label + ': ni podatka';
                            }

                            const dates = getChartDates();
                            const date = dates[context.dataIndex];

                            const source = selectedDays === '24h'
                                ? hourlyHistory
                                : history;

                            const raw = source[date]?.[context.dataset.label]?.[pollutantSelect.value];

                            const displayValue = raw === null || raw === undefined
                                ? context.parsed.y
                                : String(raw).trim();

                            return context.dataset.label +
                                ': ' +
                                displayValue +
                                ' µg/m³';
                        }
                    }
                }
            },

            scales: {
                x: {
                    grid: {
                        display: true,
                        color: () => selectedDays >= 90
                            ? 'transparent'
                            : 'rgba(0, 0, 0, 0.16)',
                        drawTicks: false
                    },

                    ticks: {
                        autoSkip: false,
                        maxRotation: 0,
                        minRotation: 0,

                        callback(value, index) {
                            const dates = getChartDates();
                            const dateString = dates[index];

                            if (!dateString) {
                                return '';
                            }

                            if (selectedDays === '24h') {
                                const hour = Number(dateString.slice(11, 13));
                                const interval = window.innerWidth <= 600 ? 4 : 3;

                                return hour % interval === 0
                                    ? `${String(hour).padStart(2, '0')}:00`
                                    : '';
                            }

                            const [year, month, day] = dateString
                                .split('-')
                                .map(Number);

                            if (selectedDays >= 90) {
                                const months = [
                                    'JAN', 'FEB', 'MAR',
                                    'APR', 'MAJ', 'JUN',
                                    'JUL', 'AVG', 'SEP',
                                    'OKT', 'NOV', 'DEC'
                                ];

                                const monthStart = dates.findIndex(date =>
                                    Number(date.slice(5, 7)) === month &&
                                    Number(date.slice(0, 4)) === year
                                );

                                let monthEnd = monthStart;

                                while (
                                    monthEnd + 1 < dates.length &&
                                    dates[monthEnd + 1].slice(0, 7) === dateString.slice(0, 7)
                                ) {
                                    monthEnd++;
                                }

                                const middleIndex = Math.floor((monthStart + monthEnd) / 2);

                                if (selectedDays === 360 && window.innerWidth <= 600) {
                                    // Poiščemo prvi celoten mesec v prikazanem obdobju.
                                    const firstDate = dates[0];
                                    const firstMonthIsComplete = firstDate.slice(8, 10) === '01';

                                    const firstFullMonth = firstMonthIsComplete
                                        ? firstDate.slice(0, 7)
                                        : dates.find(date => date.slice(8, 10) === '01')?.slice(0, 7);

                                    if (!firstFullMonth) {
                                        return '';
                                    }

                                    const firstYear = Number(firstFullMonth.slice(0, 4));
                                    const firstMonth = Number(firstFullMonth.slice(5, 7));

                                    const monthsSinceFirst =
                                        (year - firstYear) * 12 + (month - firstMonth);

                                    return index === middleIndex && monthsSinceFirst % 2 === 0
                                        ? months[month - 1]
                                        : '';
                                }

                                return index === middleIndex
                                    ? months[month - 1]
                                    : '';
                            }

                            const weekday = new Date(
                                year,
                                month - 1,
                                day
                            ).getDay();

                            if (selectedDays === 30 && window.innerWidth <= 600) {
                                return weekday === 3
                                    ? formatDate(dateString)
                                    : '';
                            }

                            if (selectedDays === 14 && window.innerWidth <= 600) {
                                if (weekday === 0) return 'NED';
                                if (weekday === 3) return formatDate(dateString);
                                return '';
                            }

                            if (weekday === 6) return 'SOB';
                            if (weekday === 0) return 'NED';
                            

                          if (selectedDays === 7) {
                              return formatDate(dateString);
                          }

                          if (weekday === 2 || weekday === 4) {
                              return formatDate(dateString);
                          }

                          return '';
                        },

                        color: '#555555',

                        font: {
                            family: 'Roboto, monospace',
                            size: 11
                        }
                    }
                },

                y: {
                    beginAtZero: true,

                    title: {
                        display: true,
                        text: 'µg/m³',
                        color: '#555555',

                        font: {
                            family: 'Roboto Mono, monospace',
                            size: 11
                        }
                    },

                    grid: {
                        color: 'rgba(0, 0, 0, 0.10)'
                    },

                    ticks: {
                        color: '#555555',

                        font: {
                            family: 'Roboto Mono, monospace',
                            size: 10
                        }
                    }
                }
            }
        }
    });

    window.historyChart = chart;

    // ======================================
    // POSODOBITEV GRAFA
    // ======================================

    function updateChart() {
        const pollutant = pollutantSelect.value;
        const dates = getChartDates();
        const reference = getCurrentReference();

        chart.data.labels = selectedDays === '24h'
            ? dates.map(date => date.slice(11))
            : dates.map(formatDate);

        chart.data.datasets = createDatasets(
            dates,
            pollutant
        );

        const measurements = chart.data.datasets
            .flatMap(dataset => dataset.data)
            .filter(value =>
                typeof value === 'number' &&
                Number.isFinite(value)
            );

        const measuredMax = measurements.length
            ? Math.max(...measurements)
            : 0;

        let referenceMax = 0;

        if (reference?.type === 'who') {
            referenceMax = reference.value;
        }

        if (reference?.type === 'eea') {
            const thresholds = reference.thresholds;

            referenceMax = thresholds.find(
                value => value >= measuredMax
            ) ?? thresholds[thresholds.length - 1];
        }

        const upperValue = Math.max(
            measuredMax,
            referenceMax,
            1
        );

        chart.options.scales.y.suggestedMax =
            upperValue * 1.12;

        chart.update();

        referenceInfo.update(reference);

        if (chartNote) {
            if (selectedDays === '24h') {
                chartNote.textContent =
                    'VIR MERITEV: ARSO. ' +
                    'Prikazane so urne koncentracije.';
            } else if (pollutant === 'NO2') {
                chartNote.textContent =
                    'VIR MERITEV: ARSO. ' +
                    'Dnevna povprečja NO₂ so izračunana iz urnih meritev.';
            } else {
                chartNote.textContent =
                    'VIR MERITEV: ARSO. ' +
                    'Prikazana obdobja povprečenja so: ' +
                    'PM2.5 in PM10: 24-urno povprečje; ' +
                    'O₃: največje povprečje 8-urnega intervala v enem dnevu.' ;
            }
        }

        const hasMeasurements = measurements.length > 0;

        if (fallback) {
            fallback.hidden = hasMeasurements;

            if (!hasMeasurements) {
                fallback.textContent =
                    'Za izbrano onesnaževalo in obdobje ' +
                    'ni razpoložljivih meritev.';
            }
        }

        canvas.hidden = !hasMeasurements;
    }

    pollutantSelect.addEventListener(
        'change',
        updateChart
    );

    rangeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const days = button.dataset.days === '24h'
                ? '24h'
                : Number(button.dataset.days);

            if (!['24h', 7, 14, 30, 90, 180, 360].includes(days)) {
                return;
            }

            selectedDays = days;

            rangeButtons.forEach(otherButton => {
                const active = otherButton === button;

                otherButton.classList.toggle(
                    'active',
                    active
                );

                otherButton.setAttribute(
                    'aria-pressed',
                    String(active)
                );
            });

            updateChart();
        });
    });

    updateChart();
}

// ==========================================
// 10. ZAGON APLIKACIJE
// ==========================================

document.addEventListener(
    'DOMContentLoaded',
    () => {
        initializeStationSelection();
        initializeHistoryChart();
        initializeSectionNavigation();
    }
);
