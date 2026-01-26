import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import HomePage from '../views/HomePage.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import ForgotPassword from '../views/ForgotPassword.vue'
import Dashboard from '../views/Dashboard.vue'
import { auth } from '../firebase'
import { onAuthStateChanged } from 'firebase/auth'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/home',
    name: 'Home',
    component: HomePage
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { requiresAuth: false }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: ForgotPassword,
    meta: { requiresAuth: false }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Guard de navigation
router.beforeEach((to, from, next) => {
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)

  // Vérifier l'état d'authentification
  onAuthStateChanged(auth, (user) => {
    if (requiresAuth && !user) {
      // Rediriger vers login si la route nécessite une auth et user non connecté
      next('/login')
    } else if (!requiresAuth && user && (to.path === '/login' || to.path === '/register')) {
      // Rediriger vers dashboard si user connecté et essaie d'accéder à login/register
      next('/dashboard')
    } else {
      // Continuer normalement
      next()
    }
  })
})

export default router
