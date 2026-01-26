<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar>
        <ion-title>Blank</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Blank</ion-title>
        </ion-toolbar>
      </ion-header>

      <div id="container">
        <strong>Prêt à créer une app ?</strong>
        <p>Démarrez avec Ionic UI Components</p>
        <ion-button router-link="/login" class="home-btn">Se connecter</ion-button>
        <ion-button router-link="/register" fill="outline" class="home-btn">S'inscrire</ion-button>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonContent, IonHeader, IonPage, IonTitle, IonToolbar, IonButton } from '@ionic/vue';
</script>

<style scoped>
#container {
  text-align: center;
  
  position: absolute;
  left: 0;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
}

#container strong {
  font-size: 20px;
  line-height: 26px;
}

#container p {
  font-size: 16px;
  line-height: 22px;
  
  color: #8c8c8c;
  
  margin: 0;
}

#container a {
  text-decoration: none;
}

.home-btn {
  margin: 10px;
  --border-radius: 8px;
}

.home-btn:first-of-type {
  --background: #1877f2;
  --color: white;
}

.home-btn:last-of-type {
  --border-color: #1877f2;
  --color: #1877f2;
}
</style>
