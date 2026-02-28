const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue'), meta: {requiresAuth: true} },
      {
        path: '/usuarios',
        component: () => import('pages/usuarios/Usuarios.vue'),
        meta: {requiresAuth: true, perm: 'Usuarios'}
      },
      {
        path:'/cambiar-contrasena',
        component: () => import('pages/cambiar-contrasena/CambiarContrasena.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: '/campeonatos',
        component: () => import('pages/campeonatos/Campeonatos.vue'),
        meta: { requiresAuth: true }
      }

    ]
  },
  {
    path: '/login',
    component: () => import('layouts/Login.vue'),
  },
  {
    path: '/register',
    component: () => import('layouts/Register.vue'),
  },
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes

