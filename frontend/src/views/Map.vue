<template>
  <ion-page>
    <ion-header>
      <ion-toolbar class="map-toolbar">
        <ion-buttons slot="start">
          <ion-back-button></ion-back-button>
        </ion-buttons>
        <ion-title>Carte des Routes - Antananarivo</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="map-content" fullscreen>
      <div id="map" ref="mapContainer" class="map-container"></div>
      
      <!-- Contrôles de zoom -->
      <div class="zoom-controls">
        <ion-button class="zoom-btn" @click="zoomIn">
          <ion-icon name="add"></ion-icon>
        </ion-button>
        <ion-button class="zoom-btn" @click="zoomOut">
          <ion-icon name="remove"></ion-icon>
        </ion-button>
        <ion-button class="zoom-btn reset-btn" @click="resetZoom">
          <ion-icon name="home"></ion-icon>
        </ion-button>
      </div>

      <!-- Panneau d'informations des routes -->
      <div :class="['roads-info-panel', { collapsed: panelCollapsed }]">
        <div class="panel-header" @click="togglePanel">
          <div class="header-content">
            <h3>{{ roads.length }} Routes</h3>
            <p class="subtitle">Antananarivo</p>
          </div>
          <ion-icon :name="panelCollapsed ? 'chevron-up-outline' : 'chevron-down-outline'" class="toggle-icon"></ion-icon>
        </div>
        
        <div v-if="!panelCollapsed" class="roads-list-wrapper">
          <ion-list class="roads-list">
            <ion-item v-for="(road, index) in roads" :key="road.id" @click="centerMapOnRoad(road)" class="road-info-item">
              <ion-icon slot="start" name="location" class="road-icon"></ion-icon>
              <ion-label>
                <h4>{{ index + 1 }}. {{ road.designation }}</h4>
                <p>📍 Lat: {{ Number(road.latitude).toFixed(4) }}, Lon: {{ Number(road.longitude).toFixed(4) }}</p>
              </ion-label>
            </ion-item>
          </ion-list>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonButton, IonButtons, IonIcon, IonBackButton } from '@ionic/vue';
import { addIcons } from 'ionicons';
import { add, remove, home, map as mapIcon, location, list, alertCircle, chevronUpOutline, chevronDownOutline, navigate } from 'ionicons/icons';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import api from '../services/api';
import { Geolocation } from '@capacitor/geolocation';

// Enregistrer les icônes
addIcons({
  add, remove, home, map: mapIcon, location, list, alertCircle, navigate,
  'chevron-up-outline': chevronUpOutline,
  'chevron-down-outline': chevronDownOutline
});

// Corriger les icônes par défaut de Leaflet pour Vite
import markerIcon from 'leaflet/dist/images/marker-icon.png?url';
import markerShadow from 'leaflet/dist/images/marker-shadow.png?url';

// Créer une icône personnalisée pour les routes
const createCustomIcon = (color: string = '#1877f2') => {
  return L.divIcon({
    html: `
      <div style="
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background-color: ${color};
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        cursor: pointer;
      ">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm0-13c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5z"/>
        </svg>
      </div>
    `,
    className: 'custom-marker',
    iconSize: [40, 40],
    iconAnchor: [20, 20],
    popupAnchor: [0, -20],
  });
};

const DefaultIcon = L.icon({
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41],
});

L.Marker.prototype.setIcon(DefaultIcon);

interface Road {
  id: number;
  designation: string;
  longitude: number;
  latitude: number;
  area: number;
}

const mapContainer = ref<HTMLElement | null>(null);
const roads = ref<Road[]>([]);
const panelCollapsed = ref(false);
let map: L.Map | null = null;
let markerGroup: L.FeatureGroup | null = null;
const markers: { [key: number]: any } = {};
let userMarker: L.Marker | null = null;
let userCircle: L.Circle | null = null;

// Créer un icône personnalisé pour l'utilisateur
const createUserIcon = () => {
  return L.divIcon({
    html: `
      <div style="
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        background: radial-gradient(circle, #4CAF50 0%, #2E7D32 100%);
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: 0 2px 12px rgba(76, 175, 80, 0.6), inset 0 0 0 3px rgba(255, 255, 255, 0.5);
        cursor: pointer;
        position: relative;
      ">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="2">
          <circle cx="12" cy="12" r="8"/>
          <path d="M12 2v8m0 4v8M2 12h8m4 0h8"/>
        </svg>
        <div style="
          position: absolute;
          bottom: -8px;
          right: -8px;
          width: 16px;
          height: 16px;
          background: #1976d2;
          border-radius: 50%;
          border: 2px solid white;
          font-size: 10px;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-weight: bold;
        ">✓</div>
      </div>
    `,
    iconSize: [50, 50],
    iconAnchor: [25, 25],
    popupAnchor: [0, -25],
  });
};

// Obtenir la position utilisateur
const getUserLocation = async () => {
  try {
    console.log('📍 Demande de localisation...');
    const coordinates = await Geolocation.getCurrentPosition({
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0,
    });
    
    const { latitude, longitude, accuracy } = coordinates.coords;
    console.log(`✓ Position obtenue: ${latitude}, ${longitude}, Precision: ${accuracy}m`);
    
    if (!map) return;
    
    // Supprimer l'ancien marqueur s'il existe
    if (userMarker) {
      map.removeLayer(userMarker);
    }
    if (userCircle) {
      map.removeLayer(userCircle);
    }
    
    // Ajouter le cercle de précision
    userCircle = L.circle([latitude, longitude], {
      color: '#4CAF50',
      weight: 2,
      opacity: 0.3,
      fill: true,
      fillColor: '#4CAF50',
      fillOpacity: 0.1,
      radius: accuracy || 50,
    }).addTo(map);
    
    // Ajouter le marqueur utilisateur
    userMarker = L.marker([latitude, longitude], {
      icon: createUserIcon(),
      zIndexOffset: 1000,
    })
      .bindPopup(`
        <div style="text-align: center; font-size: 13px; font-family: Arial;">
          <strong style="color: #4CAF50;">📍 Votre Position</strong><br>
          Lat: ${latitude.toFixed(6)}<br>
          Lon: ${longitude.toFixed(6)}<br>
          Précision: ${accuracy ? accuracy.toFixed(0) : '?'} m
        </div>
      `)
      .addTo(map)
      .openPopup();
    
    // Centrer la carte sur l'utilisateur avec un bon zoom
    map.setView([latitude, longitude], 17);
  } catch (error: any) {
    console.error('❌ Erreur de géolocalisation:', error);
    alert(`Impossible de vous localiser: ${error.message}`);
  }
};

// Charger les routes depuis l'API
const fetchRoads = async () => {
  try {
    const response = await api.get('/roads');
    // Convertir les coordonnées en nombres
    roads.value = response.data.map((road: any) => ({
      ...road,
      latitude: typeof road.latitude === 'string' ? parseFloat(road.latitude) : road.latitude,
      longitude: typeof road.longitude === 'string' ? parseFloat(road.longitude) : road.longitude,
      area: typeof road.area === 'string' ? parseFloat(road.area) : road.area,
    }));
    console.log('Routes chargées :', roads.value.length, roads.value[0]);
    
    // Créer un groupe pour les marqueurs
    if (!markerGroup && map) {
      markerGroup = L.featureGroup().addTo(map);
    }
    
    // Ajouter les marqueurs sur la carte
    roads.value.forEach(road => {
      addMarkerToMap(road);
    });
    
    // Ajuster automatiquement la vue pour voir tous les marqueurs
    if (roads.value.length > 0 && markerGroup) {
      setTimeout(() => {
        fitAllMarkers();
      }, 500);
    }
  } catch (error) {
    console.error('Erreur lors de la récupération des routes', error);
  }
};

// Ajouter un marqueur à la carte
const addMarkerToMap = (road: Road) => {
  if (!map || !markerGroup) return;
  
  // Créer le popup avec une meilleure mise en forme
  const popupContent = `
    <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; width: 260px;">
      <div style="
        background: linear-gradient(135deg, #1877f2 0%, #1255c0 100%);
        color: white;
        padding: 10px 12px;
        border-radius: 6px 6px 0 0;
        margin: -12px -12px 10px -12px;
      ">
        <h3 style="margin: 0; font-size: 15px; font-weight: 600;">📍 ${road.designation}</h3>
      </div>
      
      <div style="padding: 0 12px;">
        <div style="margin-bottom: 8px;">
          <strong style="color: #1877f2;">Latitude:</strong><br>
          <span style="color: #555; font-family: monospace;">${road.latitude.toFixed(6)}</span>
        </div>
        
        <div style="margin-bottom: 8px;">
          <strong style="color: #1877f2;">Longitude:</strong><br>
          <span style="color: #555; font-family: monospace;">${road.longitude.toFixed(6)}</span>
        </div>
        
        <div style="
          background: #f5f5f5;
          border-left: 4px solid #28a745;
          padding: 8px;
          border-radius: 4px;
          margin-bottom: 8px;
        ">
          <strong style="color: #28a745;">Zone:</strong><br>
          <span style="color: #555; font-size: 14px;">${road.area} km²</span>
        </div>
        
        <div style="
          background: #fff3cd;
          border-left: 4px solid #ffc107;
          padding: 8px;
          border-radius: 4px;
          font-size: 12px;
          color: #856404;
        ">
          ℹ️ Cliquez sur l'icône pour plus de détails
        </div>
      </div>
    </div>
  `;
  
  // Créer le marqueur avec l'icône personnalisée
  const marker = L.marker([road.latitude, road.longitude], {
    icon: createCustomIcon('#1877f2'),
  })
    .bindPopup(popupContent, {
      maxWidth: 300,
      className: 'custom-popup',
      minWidth: 260,
    });
  
  marker.on('click', () => {
    console.log(`Route cliquée: ${road.designation}`);
  });
  
  marker.addTo(markerGroup);
  markers[road.id] = marker;
};

// Centrer la carte sur une route
const centerMapOnRoad = (road: Road) => {
  if (!map) return;
  
  map.setView([road.latitude, road.longitude], 16);
  
  // Ouvrir le popup du marqueur
  const marker = markers[road.id];
  if (marker) {
    marker.openPopup();
  }
};

// Ajuster la vue pour voir tous les marqueurs
const fitAllMarkers = () => {
  if (!map || !markerGroup || roads.value.length === 0) return;
  
  const bounds = markerGroup.getBounds();
  if (bounds.isValid()) {
    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
  }
};

// Zoom in
const zoomIn = () => {
  if (map) {
    map.zoomIn();
  }
};

// Zoom out
const zoomOut = () => {
  if (map) {
    map.zoomOut();
  }
};

// Reset zoom
const resetZoom = () => {
  fitAllMarkers();
};

// Toggle du panneau
const togglePanel = () => {
  panelCollapsed.value = !panelCollapsed.value;
};

// Initialiser la carte
const initMap = () => {
  if (!mapContainer.value || map) return;
  
  // Créer la carte avec les coordonnées d'Antananarivo
  map = L.map(mapContainer.value, {
    zoomControl: false, // Désactiver les contrôles par défaut
    attributionControl: true,
  }).setView([-18.8788, 47.5227], 12);
  
  // Ajouter la couche OpenStreetMap
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 20,
    minZoom: 10,
  }).addTo(map);
  
  // Ajouter l'échelle
  L.control.scale({ position: 'bottomright' }).addTo(map);
  
  console.log('Carte Leaflet initialisée pour Antananarivo');
};

onMounted(() => {
  console.log('Map.vue montée');
  // Attendre que le DOM soit prêt
  setTimeout(() => {
    initMap();
    fetchRoads();
  }, 100);
});
</script>

<style scoped>
:deep(.map-content) {
  --padding-start: 0;
  --padding-end: 0;
  --padding-top: 0;
  --padding-bottom: 0;
  --offset-top: 0;
  --offset-bottom: 0;
}

.map-toolbar {
  --background: #1877f2;
  --color: white;
  --border-color: transparent;
}

.map-content {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.map-container {
  width: 100% !important;
  height: 100% !important;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
}

/* Contrôles de zoom */
.zoom-controls {
  position: fixed;
  bottom: 240px;
  right: 15px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 999;
}

.zoom-btn {
  --background: white;
  --color: #1877f2;
  --border-radius: 50%;
  width: 50px;
  height: 50px;
  min-width: 50px;
  min-height: 50px;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.25);
  --padding-start: 0;
  --padding-end: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: bold;
}

.zoom-btn:active {
  --background: #f0f0f0;
  box-shadow: 0 3px 15px rgba(0, 0, 0, 0.35);
}

.zoom-btn.reset-btn {
  margin-top: 5px;
}

/* Panneau d'informations des routes */
.roads-info-panel {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  border-radius: 16px 16px 0 0;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
  z-index: 998;
  max-height: 60vh;
  overflow: hidden;
  transition: max-height 0.3s ease, transform 0.3s ease;
  transform: translateY(0);
}

.roads-info-panel.collapsed {
  max-height: 60px;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: linear-gradient(135deg, #1877f2 0%, #1255c0 100%);
  color: white;
  border-radius: 16px 16px 0 0;
  cursor: pointer;
  user-select: none;
  flex-shrink: 0;
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.panel-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
}

.panel-header .subtitle {
  margin: 0;
  font-size: 12px;
  opacity: 0.9;
}

.toggle-icon {
  font-size: 24px;
  cursor: pointer;
}

.roads-list-wrapper {
  height: calc(60vh - 70px);
  overflow-y: auto;
  overflow-x: hidden;
}

.roads-list {
  padding: 8px 0;
  margin: 0;
  background: white;
}

.roads-list::before,
.roads-list::after {
  display: none;
}

.road-info-item {
  --padding-start: 12px;
  --padding-end: 12px;
  --min-height: 60px;
  --border-color: #f0f0f0;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.1s;
}

.road-info-item:last-child {
  border-bottom: none;
}

.road-info-item:active {
  --background: #f5f5f5;
  transform: scale(0.98);
}

.road-icon {
  color: #1877f2;
  font-size: 20px;
  margin-right: 8px;
}

.road-info-item h4 {
  color: #1877f2;
  margin: 0;
  font-size: 14px;
  font-weight: 600;
}

.road-info-item p {
  color: #666;
  margin: 4px 0 0 0;
  font-size: 12px;
}

/* Scrollbar personnalisée */
.roads-list-wrapper::-webkit-scrollbar {
  width: 8px;
}

.roads-list-wrapper::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.roads-list-wrapper::-webkit-scrollbar-thumb {
  background: #1877f2;
  border-radius: 4px;
}

.roads-list-wrapper::-webkit-scrollbar-thumb:hover {
  background: #1255c0;
}

/* Mobile responsif */
@media (max-width: 768px) {
  .roads-info-panel {
    max-height: 50vh;
  }
  
  .roads-list-wrapper {
    height: calc(50vh - 70px);
  }
  
  .zoom-controls {
    bottom: 200px;
  }
}

/* Style personnalisé des popups Leaflet */
:deep(.custom-popup) {
  .leaflet-popup-content-wrapper {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(24, 119, 242, 0.15);
    border: 2px solid #1877f2;
    padding: 0;
    overflow: hidden;
  }

  .leaflet-popup-content {
    margin: 0;
    padding: 0;
    line-height: 1.5;
  }

  .leaflet-popup-tip-container {
    display: none;
  }
}

:deep(.leaflet-popup-tip) {
  background: white;
  border: 2px solid #1877f2;
}

/* Animation de zoom lors du clic */
:deep(.leaflet-marker-icon) {
  transition: transform 0.2s ease;
}

:deep(.leaflet-marker-icon:hover) {
  transform: scale(1.1);
}

:deep(.leaflet-marker-icon.active) {
  filter: drop-shadow(0 0 8px rgba(24, 119, 242, 0.6));
}
</style>


