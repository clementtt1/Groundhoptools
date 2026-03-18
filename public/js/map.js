document.addEventListener('DOMContentLoaded', () => {
    if (document.body.id !== 'homepage') return;

    // --- Récupération des données Twig via data-attributes ---
    const mapDiv = document.getElementById('map');
    if (!mapDiv) return;

    const clubs    = JSON.parse(mapDiv.dataset.clubs);
    const apiUrl   = mapDiv.dataset.apiUrl;
    const logosUrl = mapDiv.dataset.logosUrl;

    // --- Initialisation de la carte ---
    const map = L.map('map').setView([46.8, 2.3], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    let markers = [];

    // --- Construction du contenu popup d'un club ---
    function buildPopupContent(club, matchs) {
        const matchsClub = matchs.filter(
            m => m.homeTeam.toLowerCase() === club.name.toLowerCase()
        );

        let content = `<b>${club.name}</b><br>${club.stadium ?? ''}`;

        if (matchsClub.length > 0) {
            content += `<br><br><b>Matchs à domicile :</b><br>`;
            matchsClub.forEach(m => {
                const date  = new Date(m.startTime);
                const jour  = date.toLocaleDateString('fr-FR');
                const heure = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                content += `${jour} - ${heure} 🆚 ${m.awayTeam}<br>`;
            });
        }

        return content;
    }

    // --- Création d'un marker pour un club ---
    function createMarker(club, matchs) {
        const icon = L.icon({
            iconUrl:     logosUrl + club.logo,
            iconSize:    [40, 40],
            iconAnchor:  [20, 40],
            popupAnchor: [0, -40]
        });

        return L.marker([club.latitude, club.longitude], { icon })
            .bindPopup(buildPopupContent(club, matchs));
    }

    // --- Affichage d'une liste de clubs sur la carte ---
    function afficherClubs(liste, matchs = []) {
        markers.forEach(m => map.removeLayer(m));
        markers = liste.map(club => createMarker(club, matchs).addTo(map));
    }

    // --- Chargement des matchs depuis l'API ---
    async function chargerMatchs(startDate, endDate, hideVisited = 0) {
        try {
            const url = `${apiUrl}?start=${startDate.format('YYYY-MM-DD')}&end=${endDate.format('YYYY-MM-DD')}&hideVisited=${hideVisited}`;
            const response = await fetch(url);
            const data = await response.json();

            if (!data.events?.length) {
                afficherClubs([]);
                return;
            }

            const nomsDomicile = new Set(data.events.map(e => e.homeTeam.toLowerCase()));
            const clubsFiltres = clubs.filter(c => nomsDomicile.has(c.name.toLowerCase()));
            afficherClubs(clubsFiltres, data.events);

        } catch (err) {
            console.error('Erreur chargement matchs:', err);
            alert('Impossible de charger les données.');
        }
    }

    // --- Affichage initial ---
    afficherClubs(clubs);

    // --- DateRangePicker ---
    let startDate = moment();
    let endDate   = moment();

    $('#daterange').daterangepicker({
        startDate,
        endDate,
        autoUpdateInput: true,
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Appliquer',
            cancelLabel: 'Annuler',
            fromLabel: 'De',
            toLabel: 'À',
            daysOfWeek:  ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
            monthNames:  ['Janvier','Février','Mars','Avril','Mai','Juin',
                          'Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
            firstDay: 1
        }
    }, (start, end) => {
        startDate = start;
        endDate   = end;
    });

    // --- Événements UI ---
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
});