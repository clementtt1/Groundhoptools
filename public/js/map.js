document.addEventListener('DOMContentLoaded', () => {
    if (document.body.id !== 'homepage') return;

    // --- Données Twig via data-attributes ---
    const mapDiv  = document.getElementById('map');
    if (!mapDiv) return;

    const clubs    = JSON.parse(mapDiv.dataset.clubs);
    const apiUrl   = mapDiv.dataset.apiUrl;
    const logosUrl = mapDiv.dataset.logosUrl;

    // --- Constantes ---
    const USER_ICON_URL = 'https://cdn-icons-png.flaticon.com/512/149/149060.png';
    const ROUTE_COLORS  = ['#e74c3c', '#3498db', '#2ecc71', '#f1c40f', '#9b59b6', '#e67e22'];

    // --- État ---
    let markers      = [];
    let routeLayers  = [];
    let userSchedule = [];
    let userPos      = null;

    // --- Carte ---
    const map = L.map('map').setView([46.8, 2.3], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    // --- Géolocalisation ---
    navigator.geolocation.getCurrentPosition(pos => {
        userPos = {
            lat:      pos.coords.latitude,
            lng:      pos.coords.longitude,
            clubName: 'Ma position',
            logo:     USER_ICON_URL
        };
    });

    // --- Utilitaires ---
    function formatDate(isoString) {
        const d = new Date(isoString);
        return d.toLocaleDateString('fr-FR');
    }

    function formatTime(isoString) {
        const d = new Date(isoString);
        return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    }

    function formatDuration(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        return `${h}h${m}m`;
    }

    function formatDistance(meters) {
        return `${(meters / 1000).toFixed(1)} km`;
    }

    function getLogoSrc(logo) {
        return logo.startsWith('http') ? logo : logosUrl + logo;
    }

    // --- Affichage des clubs ---
    function buildPopupContent(club, matchs) {
        const matchsClub = matchs.filter(
            m => m.homeTeam.toLowerCase() === club.name.toLowerCase()
        );

        let popup = `<b>${club.name}</b><br>${club.stadium ?? ''}`;

        if (matchsClub.length > 0) {
            popup += `<br><br><b>Matchs :</b><br>`;
            matchsClub.forEach(m => {
                popup += `${formatDate(m.startTime)} - ${formatTime(m.startTime)} 🆚 ${m.awayTeam}`;
                popup += `<br><button class="btn btn-sm btn-outline-primary mt-1"
                    onclick="ajouterMatchProgramme(
                        ${club.latitude}, ${club.longitude},
                        '${club.name}', '${m.homeTeam}', '${m.awayTeam}',
                        '${m.startTime}', '${club.logo}'
                    )">Ajouter</button><br>`;
            });
        }

        return popup;
    }

    function afficherClubs(liste, matchs = []) {
        markers.forEach(m => map.removeLayer(m));
        markers = liste.map(club => {
            const icon = L.icon({
                iconUrl:     logosUrl + club.logo,
                iconSize:    [40, 40],
                iconAnchor:  [20, 40],
                popupAnchor: [0, -40]
            });
            return L.marker([club.latitude, club.longitude], { icon })
                .bindPopup(buildPopupContent(club, matchs))
                .addTo(map);
        });
    }

    afficherClubs(clubs);

    // --- Chargement des matchs ---
    async function chargerMatchs(startDate, endDate, hideVisited = 0) {
        try {
            const url = `${apiUrl}?start=${startDate.format('YYYY-MM-DD')}&end=${endDate.format('YYYY-MM-DD')}&hideVisited=${hideVisited}`;
            const data = await fetch(url).then(r => r.json());

            if (!data.events?.length) { afficherClubs([]); return; }

            const domicile = new Set(data.events.map(e => e.homeTeam.toLowerCase()));
            afficherClubs(clubs.filter(c => domicile.has(c.name.toLowerCase())), data.events);
        } catch (err) {
            console.error('Erreur chargement matchs:', err);
            alert('Impossible de charger les données.');
        }
    }

    // --- DateRangePicker ---
    let startDate = moment();
    let endDate   = moment();

    $('#daterange').daterangepicker({
        startDate, endDate, autoUpdateInput: true,
        locale: {
            format: 'DD/MM/YYYY', firstDay: 1,
            applyLabel: 'Appliquer', cancelLabel: 'Annuler',
            daysOfWeek:  ['Di','Lu','Ma','Me','Je','Ve','Sa'],
            monthNames:  ['Janvier','Février','Mars','Avril','Mai','Juin',
                          'Juillet','Août','Septembre','Octobre','Novembre','Décembre']
        }
    }, (start, end) => { startDate = start; endDate = end; });

    const hideVisitedCheckbox = document.getElementById('hide-visited');

    function getHideVisited() {
        return hideVisitedCheckbox?.checked ? 1 : 0;
    }

    document.getElementById('load-matches').addEventListener('click', () => {
        chargerMatchs(startDate, endDate, getHideVisited());
    });

    hideVisitedCheckbox?.addEventListener('change', () => {
        chargerMatchs(startDate, endDate, getHideVisited());
    });

    // --- Programme de groundhopping ---
    window.ajouterMatchProgramme = async function(lat, lng, clubName, homeTeam, awayTeam, startTime, logo) {
        if (userSchedule.some(m => m.startTime === startTime && m.clubName === clubName)) {
            alert('Déjà ajouté.'); return;
        }
        userSchedule.push({ lat, lng, clubName, homeTeam, awayTeam, startTime, logo });
        userSchedule.sort((a, b) => new Date(a.startTime) - new Date(b.startTime));
        await afficherProgramme();
    };

    window.supprimerMatch = async function(startTime, clubName) {
        userSchedule = userSchedule.filter(
            m => !(m.startTime === startTime && m.clubName === clubName)
        );
        await afficherProgramme();
    };

    async function fetchRoute(start, end) {
        const url = `/api/route?startLat=${start.lat}&startLng=${start.lng}&endLat=${end.lat}&endLng=${end.lng}`;
        return fetch(url).then(r => r.json());
    }

    function buildScheduleItem(step) {
        const li   = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';

        const left = document.createElement('div');
        left.className = 'd-flex align-items-center gap-2';

        const img  = document.createElement('img');
        img.src    = getLogoSrc(step.logo);
        img.width  = 28;
        left.appendChild(img);

        let text = step.clubName;
        if (step.startTime) {
            text += ` (${formatDate(step.startTime)} ${formatTime(step.startTime)}) 🆚 ${step.awayTeam}`;
        }
        left.appendChild(document.createTextNode(text));
        li.appendChild(left);

        if (step.startTime) {
            const btn     = document.createElement('button');
            btn.className = 'btn btn-sm btn-outline-danger';
            btn.innerHTML = '🗑';
            btn.onclick   = () => supprimerMatch(step.startTime, step.clubName);
            li.appendChild(btn);
        }

        return li;
    }

    function buildRouteItem(data, from, to) {
        const li = document.createElement('li');
        li.className = 'list-group-item text-secondary text-center';
        li.textContent = `↓ ${formatDistance(data.distance)} | ${formatDuration(data.duration)} (${from.clubName} → ${to.clubName})`;
        return li;
    }

    async function afficherProgramme() {
        const list     = document.getElementById('schedule-list');
        const totalDiv = document.getElementById('schedule-total');
        list.innerHTML = '';
        totalDiv.innerHTML = '';
        routeLayers.forEach(l => map.removeLayer(l));
        routeLayers = [];

        if (!userSchedule.length || !userPos) return;

        const fullRoute  = [userPos, ...userSchedule, userPos];
        const routeData  = await Promise.all(
            fullRoute.slice(0, -1).map((step, i) => fetchRoute(step, fullRoute[i + 1]))
        );

        let totalDistance = 0;
        let totalDuration = 0;

        fullRoute.forEach((step, i) => {
            list.appendChild(buildScheduleItem(step));

            if (i < fullRoute.length - 1) {
                const data  = routeData[i];
                const color = ROUTE_COLORS[i % ROUTE_COLORS.length];

                const segment = L.polyline(polyline.decode(data.geometry), { color, weight: 5 })
                    .bindPopup(`<b>Trajet :</b> ${step.clubName} → ${fullRoute[i+1].clubName}<br>
                                <b>Distance :</b> ${formatDistance(data.distance)}<br>
                                <b>Durée :</b> ${formatDuration(data.duration)}`)
                    .addTo(map);

                routeLayers.push(segment);
                list.appendChild(buildRouteItem(data, step, fullRoute[i + 1]));

                totalDistance += data.distance;
                totalDuration += data.duration;
            }
        });

        map.fitBounds(new L.featureGroup(routeLayers).getBounds());
        totalDiv.textContent = `Distance totale : ${formatDistance(totalDistance)} | Durée totale : ${formatDuration(totalDuration)}`;
    }

    document.getElementById('reset-schedule').addEventListener('click', () => {
        userSchedule = [];
        routeLayers.forEach(l => map.removeLayer(l));
        routeLayers = [];
        document.getElementById('schedule-list').innerHTML  = '';
        document.getElementById('schedule-total').innerHTML = '';
    });
});